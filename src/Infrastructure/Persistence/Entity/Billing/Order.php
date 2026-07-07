<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Billing;

use App\Domain\Billing\OrderStatus;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\UniqueConstraint(name: 'uniq_orders_tribute', columns: ['tribute_id'])]
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
    #[ORM\JoinColumn(name: 'tribute_id', referencedColumnName: 'id', nullable: false)]
    private Tribute $tribute;

    #[ORM\ManyToOne(targetEntity: ProductPrice::class)]
    #[ORM\JoinColumn(name: 'product_price_id', referencedColumnName: 'id', nullable: false)]
    private ProductPrice $productPrice;

    #[ORM\Column(length: 30, enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Column(name: 'amount_cents', type: 'integer')]
    private int $amountCents;

    #[ORM\Column(name: 'mp_preference_id', length: 120, nullable: true)]
    private ?string $mpPreferenceId = null;

    #[ORM\Column(name: 'checkout_url', length: 500, nullable: true)]
    private ?string $checkoutUrl = null;

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
        Tribute $tribute,
        ProductPrice $productPrice,
        int $amountCents,
        string $externalReference,
    ) {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->tribute = $tribute;
        $this->productPrice = $productPrice;
        $this->amountCents = $amountCents;
        $this->externalReference = $externalReference !== '' ? $externalReference : 'order:'.(string) $this->id;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTribute(): Tribute
    {
        return $this->tribute;
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

    public function getCheckoutUrl(): ?string
    {
        return $this->checkoutUrl;
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
