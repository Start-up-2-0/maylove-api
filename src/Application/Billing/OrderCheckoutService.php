<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\Exception\BillingException;
use App\Domain\Billing\OrderStatus;
use App\Domain\Billing\ProductBillingMode;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Payment\MercadoPagoClient;
use App\Infrastructure\Persistence\Entity\Billing\Order;
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
        private readonly string $frontendUrl,
        private readonly string $notificationUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function checkout(Tribute $tribute, User $user): array
    {
        $product = $this->productRepository->findActivePremium();
        if ($product === null) {
            throw new BillingException('Produto não encontrado.', 'PRODUCT_NOT_FOUND', 404);
        }

        $price = $this->productPriceRepository->findByProductAndMode($product, ProductBillingMode::PerTribute);
        if ($price === null) {
            throw new BillingException('Preço avulso não encontrado.', 'PRODUCT_NOT_FOUND', 404);
        }

        $order = $this->orderRepository->findByTribute($tribute);
        if ($order !== null) {
            if ($order->getStatus() === OrderStatus::Paid) {
                throw new BillingException('Homenagem já paga.', 'ORDER_ALREADY_PAID', 409);
            }

            if ($order->getStatus() === OrderStatus::Pending && $order->getCheckoutUrl() !== null) {
                return $this->serializeCheckout($order, $tribute);
            }
        }

        if ($order === null) {
            $order = new Order(
                $user,
                $tribute,
                $price,
                $price->getPriceCents(),
                '',
            );
            $this->orderRepository->save($order);
        }

        $preference = $this->mercadoPagoClient->createCheckoutPreference(
            $this->buildPreference($user, $tribute, $order, $price),
        );

        $order->setCheckoutSession(
            (string) ($preference['id'] ?? ''),
            $this->mercadoPagoClient->resolveCheckoutUrl($preference),
        );
        $this->orderRepository->save($order);

        return $this->serializeCheckout($order, $tribute);
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

        return [
            'id' => (string) $order->getId(),
            'status' => $order->getStatus()->value,
            'amount_cents' => $order->getAmountCents(),
            'tribute_id' => (string) $order->getTribute()->getId(),
            'checkout_url' => $order->getCheckoutUrl(),
            'paid_at' => $order->getPaidAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCheckout(Order $order, Tribute $tribute): array
    {
        return [
            'order_id' => (string) $order->getId(),
            'tribute_id' => (string) $tribute->getId(),
            'status' => $order->getStatus()->value,
            'price_cents' => $order->getAmountCents(),
            'checkout_url' => $order->getCheckoutUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPreference(User $user, Tribute $tribute, Order $order, \App\Infrastructure\Persistence\Entity\Billing\ProductPrice $price): array
    {
        $title = $tribute->getTitle() ?: 'Homenagem MayLov';

        return [
            'items' => [[
                'title' => $title,
                'quantity' => 1,
                'unit_price' => $price->getPriceAmount(),
                'currency_id' => 'BRL',
            ]],
            'payer' => ['email' => $user->getEmail()],
            'external_reference' => $order->getExternalReference(),
            'notification_url' => $this->notificationUrl,
            'back_urls' => [
                'success' => rtrim($this->frontendUrl, '/').'/dashboard/tributes/'.(string) $tribute->getId().'?payment=success',
                'failure' => rtrim($this->frontendUrl, '/').'/dashboard/tributes/'.(string) $tribute->getId().'?payment=failure',
                'pending' => rtrim($this->frontendUrl, '/').'/dashboard/tributes/'.(string) $tribute->getId().'?payment=pending',
            ],
            'auto_return' => 'approved',
        ];
    }
}
