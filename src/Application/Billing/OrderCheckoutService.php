<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\Exception\BillingException;
use App\Domain\Billing\OrderStatus;
use App\Domain\Billing\ProductBillingMode;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Payment\MercadoPagoClient;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\Billing\Order;
use App\Infrastructure\Persistence\Entity\Billing\ProductPrice;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\Billing\OrderRepository;
use App\Infrastructure\Persistence\Repository\Billing\ProductPriceRepository;
use App\Infrastructure\Persistence\Repository\Billing\ProductRepository;

final class OrderCheckoutService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductPriceRepository $productPriceRepository,
        private readonly OrderRepository $orderRepository,
        private readonly MercadoPagoClient $mercadoPagoClient,
        private readonly string $notificationUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function checkoutTribute(Tribute $tribute, User $user): array
    {
        $price = $this->requirePrice(ProductBillingMode::PerTribute);
        $order = $this->orderRepository->findByTribute($tribute);

        return $this->checkoutOrder(
            $order,
            $user,
            $price,
            fn () => Order::forTribute($user, $tribute, $price, $price->getPriceCents()),
            $tribute->getTitle() ?: 'Homenagem MayLov',
            [
                'kind' => 'tribute',
                'tribute_id' => (string) $tribute->getId(),
                'album_id' => null,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function checkoutAlbum(Album $album, User $user): array
    {
        $price = $this->requirePrice(ProductBillingMode::PerAlbum);
        $order = $this->orderRepository->findByAlbum($album);

        return $this->checkoutOrder(
            $order,
            $user,
            $price,
            fn () => Order::forAlbum($user, $album, $price, $price->getPriceCents()),
            $album->getTitle() ?: 'Álbum MayLov',
            [
                'kind' => 'album',
                'tribute_id' => null,
                'album_id' => (string) $album->getId(),
            ],
        );
    }

    /**
     * @deprecated use checkoutTribute
     *
     * @return array<string, mixed>
     */
    public function checkout(Tribute $tribute, User $user): array
    {
        return $this->checkoutTribute($tribute, $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOwnedOrder(string $orderId, User $user): array
    {
        if (!UuidValue::isValid($orderId)) {
            throw new BillingException('Pedido não encontrado.', 'ORDER_NOT_FOUND', 404);
        }

        $order = $this->orderRepository->findOwnedById(
            UuidValue::fromString($orderId),
            $user->getId(),
        );
        if ($order === null) {
            throw new BillingException('Pedido não encontrado.', 'ORDER_NOT_FOUND', 404);
        }

        return $this->serializeOrder($order);
    }

    private function requirePrice(ProductBillingMode $mode): ProductPrice
    {
        $product = $this->productRepository->findActivePremium();
        if ($product === null) {
            throw new BillingException('Produto não encontrado.', 'PRODUCT_NOT_FOUND', 404);
        }

        $price = $this->productPriceRepository->findByProductAndMode($product, $mode);
        if ($price === null) {
            throw new BillingException('Preço não encontrado.', 'PRODUCT_NOT_FOUND', 404);
        }

        return $price;
    }

    /**
     * @param callable(): Order $factory
     * @param array{kind: string, tribute_id: ?string, album_id: ?string} $meta
     *
     * @return array<string, mixed>
     */
    private function checkoutOrder(
        ?Order $order,
        User $user,
        ProductPrice $price,
        callable $factory,
        string $title,
        array $meta,
    ): array {
        if ($order !== null) {
            if ($order->getStatus() === OrderStatus::Paid) {
                throw new BillingException('Pedido já pago.', 'ORDER_ALREADY_PAID', 409);
            }

            if ($order->hasActivePix()) {
                return $this->serializeCheckout($order, $meta);
            }

            if ($order->getStatus() !== OrderStatus::Pending) {
                $order->markPending();
            }
        }

        if ($order === null) {
            $order = $factory();
            $this->orderRepository->save($order);
        }

        $payment = $this->mercadoPagoClient->createPixPayment(
            [
                'transaction_amount' => $price->getPriceAmount(),
                'description' => $title,
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $user->getEmail(),
                ],
                'external_reference' => $order->getExternalReference(),
                'notification_url' => $this->notificationUrl,
            ],
            'pix-'.$order->getExternalReference().'-'.bin2hex(random_bytes(4)),
        );

        $pix = $this->mercadoPagoClient->extractPixData($payment);
        if ($pix['payment_id'] === '' || ($pix['qr_code'] === null && $pix['ticket_url'] === null)) {
            throw new BillingException(
                'Mercado Pago não retornou dados do PIX.',
                'PAYMENT_GATEWAY_ERROR',
                502,
            );
        }

        $order->setPixPayment(
            $pix['payment_id'],
            $pix['qr_code'],
            $pix['qr_code_base64'],
            $pix['ticket_url'],
        );
        $this->orderRepository->save($order);

        return $this->serializeCheckout($order, $meta);
    }

    /**
     * @param array{kind: string, tribute_id: ?string, album_id: ?string} $meta
     *
     * @return array<string, mixed>
     */
    private function serializeCheckout(Order $order, array $meta): array
    {
        return [
            'order_id' => (string) $order->getId(),
            'kind' => $meta['kind'],
            'tribute_id' => $meta['tribute_id'],
            'album_id' => $meta['album_id'],
            'status' => $order->getStatus()->value,
            'price_cents' => $order->getAmountCents(),
            'payment_method' => 'pix',
            'checkout_url' => $order->getCheckoutUrl(),
            'pix' => [
                'qr_code' => $order->getPixQrCode(),
                'qr_code_base64' => $order->getPixQrCodeBase64(),
                'ticket_url' => $order->getPixTicketUrl(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrder(Order $order): array
    {
        return [
            'id' => (string) $order->getId(),
            'status' => $order->getStatus()->value,
            'amount_cents' => $order->getAmountCents(),
            'payment_method' => 'pix',
            'tribute_id' => $order->getTribute() !== null ? (string) $order->getTribute()->getId() : null,
            'album_id' => $order->getAlbum() !== null ? (string) $order->getAlbum()->getId() : null,
            'checkout_url' => $order->getCheckoutUrl(),
            'pix' => [
                'qr_code' => $order->getPixQrCode(),
                'qr_code_base64' => $order->getPixQrCodeBase64(),
                'ticket_url' => $order->getPixTicketUrl(),
            ],
            'paid_at' => $order->getPaidAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
