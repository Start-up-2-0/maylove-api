<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Billing;

use App\Domain\Billing\OrderStatus;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\UniqueConstraint(name: 'uniq_orders_tribute', columns: ['tribute_id'])]
#[ORM\UniqueConstraint(name: 'uniq_orders_album', columns: ['album_id'])]
#[ORM\HasLifecycleCallbacks]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Tribute::class)]
    #[ORM\JoinColumn(name: 'tribute_id', referencedColumnName: 'id', nullable: true)]
    private ?Tribute $tribute = null;

    #[ORM\ManyToOne(targetEntity: Album::class)]
    #[ORM\JoinColumn(name: 'album_id', referencedColumnName: 'id', nullable: true)]
    private ?Album $album = null;

    #[ORM\ManyToOne(targetEntity: ProductPrice::class)]
    #[ORM\JoinColumn(name: 'product_price_id', referencedColumnName: 'id', nullable: false)]
    private ProductPrice $productPrice;

    #[ORM\Column(length: 30, enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Column(name: 'amount_cents', type: 'integer')]
    private int $amountCents;

    #[ORM\Column(name: 'mp_preference_id', length: 120, nullable: true)]
    private ?string $mpPreferenceId = null;

    #[ORM\Column(name: 'mp_payment_id', length: 120, nullable: true)]
    private ?string $mpPaymentId = null;

    #[ORM\Column(name: 'checkout_url', length: 500, nullable: true)]
    private ?string $checkoutUrl = null;

    #[ORM\Column(name: 'pix_qr_code', type: Types::TEXT, nullable: true)]
    private ?string $pixQrCode = null;

    #[ORM\Column(name: 'pix_qr_code_base64', type: Types::TEXT, nullable: true)]
    private ?string $pixQrCodeBase64 = null;

    #[ORM\Column(name: 'pix_ticket_url', length: 500, nullable: true)]
    private ?string $pixTicketUrl = null;

    #[ORM\Column(name: 'external_reference', length: 120)]
    private string $externalReference;

    #[ORM\Column(name: 'paid_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        User $user,
        ProductPrice $productPrice,
        int $amountCents,
        string $externalReference,
        ?Tribute $tribute = null,
        ?Album $album = null,
    ) {
        if (($tribute === null) === ($album === null)) {
            throw new \InvalidArgumentException('Order must reference exactly one of tribute or album.');
        }

        $this->id = Uuid::v7();
        $this->user = $user;
        $this->tribute = $tribute;
        $this->album = $album;
        $this->productPrice = $productPrice;
        $this->amountCents = $amountCents;
        $this->externalReference = $externalReference !== '' ? $externalReference : 'order:'.(string) $this->id;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function forTribute(
        User $user,
        Tribute $tribute,
        ProductPrice $productPrice,
        int $amountCents,
    ): self {
        return new self($user, $productPrice, $amountCents, '', $tribute, null);
    }

    public static function forAlbum(
        User $user,
        Album $album,
        ProductPrice $productPrice,
        int $amountCents,
    ): self {
        return new self($user, $productPrice, $amountCents, '', null, $album);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTribute(): ?Tribute
    {
        return $this->tribute;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function getProductPrice(): ProductPrice
    {
        return $this->productPrice;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function getMpPreferenceId(): ?string
    {
        return $this->mpPreferenceId;
    }

    public function getMpPaymentId(): ?string
    {
        return $this->mpPaymentId;
    }

    public function getCheckoutUrl(): ?string
    {
        return $this->checkoutUrl;
    }

    public function getPixQrCode(): ?string
    {
        return $this->pixQrCode;
    }

    public function getPixQrCodeBase64(): ?string
    {
        return $this->pixQrCodeBase64;
    }

    public function getPixTicketUrl(): ?string
    {
        return $this->pixTicketUrl;
    }

    public function getExternalReference(): string
    {
        return $this->externalReference;
    }

    public function setCheckoutSession(string $preferenceId, string $checkoutUrl): void
    {
        $this->mpPreferenceId = $preferenceId;
        $this->checkoutUrl = $checkoutUrl;
        $this->touch();
    }

    public function setPixPayment(
        string $mpPaymentId,
        ?string $qrCode,
        ?string $qrCodeBase64,
        ?string $ticketUrl,
    ): void {
        $this->mpPaymentId = $mpPaymentId;
        $this->pixQrCode = $qrCode;
        $this->pixQrCodeBase64 = $qrCodeBase64;
        $this->pixTicketUrl = $ticketUrl;
        if ($ticketUrl !== null && $ticketUrl !== '') {
            $this->checkoutUrl = $ticketUrl;
        }
        $this->touch();
    }

    public function hasActivePix(): bool
    {
        return $this->status === OrderStatus::Pending
            && $this->pixQrCode !== null
            && $this->pixQrCode !== '';
    }

    public function markPending(): void
    {
        $this->status = OrderStatus::Pending;
        $this->paidAt = null;
        $this->touch();
    }

    public function markPaid(): void
    {
        $this->status = OrderStatus::Paid;
        $this->paidAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function markFailed(): void
    {
        $this->status = OrderStatus::Failed;
        $this->touch();
    }

    public function markCancelled(): void
    {
        $this->status = OrderStatus::Cancelled;
        $this->touch();
    }

    public function markExpired(): void
    {
        $this->status = OrderStatus::Expired;
        $this->touch();
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->touch();
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
