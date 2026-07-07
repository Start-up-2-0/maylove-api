<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Entity\Billing;

use App\Domain\Billing\SubscriptionStatus;
use App\Infrastructure\Persistence\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user_subscriptions')]
#[ORM\UniqueConstraint(name: 'uniq_user_subscriptions_user', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'uniq_user_subscriptions_mp', columns: ['mp_subscription_id'])]
#[ORM\HasLifecycleCallbacks]
class UserSubscription
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: ProductPrice::class)]
    #[ORM\JoinColumn(name: 'product_price_id', referencedColumnName: 'id', nullable: false)]
    private ProductPrice $productPrice;

    #[ORM\Column(length: 30, enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status = SubscriptionStatus::Pending;

    #[ORM\Column(name: 'mp_subscription_id', length: 120, nullable: true, unique: true)]
    private ?string $mpSubscriptionId = null;

    #[ORM\Column(name: 'mp_preference_id', length: 120, nullable: true)]
    private ?string $mpPreferenceId = null;

    #[ORM\Column(name: 'checkout_url', length: 500, nullable: true)]
    private ?string $checkoutUrl = null;

    #[ORM\Column(name: 'current_period_start', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(name: 'current_period_end', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(name: 'cancel_at_period_end', options: ['default' => false])]
    private bool $cancelAtPeriodEnd = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, Product $product, ProductPrice $productPrice)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->product = $product;
        $this->productPrice = $productPrice;
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

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getProductPrice(): ProductPrice
    {
        return $this->productPrice;
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function setStatus(SubscriptionStatus $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    public function getMpSubscriptionId(): ?string
    {
        return $this->mpSubscriptionId;
    }

    public function setMpSubscriptionId(?string $mpSubscriptionId): void
    {
        $this->mpSubscriptionId = $mpSubscriptionId;
        $this->touch();
    }

    public function getMpPreferenceId(): ?string
    {
        return $this->mpPreferenceId;
    }

    public function setCheckoutSession(string $preferenceId, string $checkoutUrl): void
    {
        $this->mpPreferenceId = $preferenceId;
        $this->checkoutUrl = $checkoutUrl;
        $this->touch();
    }

    public function getCheckoutUrl(): ?string
    {
        return $this->checkoutUrl;
    }

    public function getCurrentPeriodStart(): ?\DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function getCurrentPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function isCancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function prepareCheckout(Product $product, ProductPrice $productPrice): void
    {
        $this->product = $product;
        $this->productPrice = $productPrice;
        $this->status = SubscriptionStatus::Pending;
        $this->mpPreferenceId = null;
        $this->checkoutUrl = null;
        $this->touch();
    }

    public function activate(\DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd, ?string $mpSubscriptionId = null): void
    {
        $this->status = SubscriptionStatus::Active;
        $this->currentPeriodStart = $periodStart;
        $this->currentPeriodEnd = $periodEnd;
        if ($mpSubscriptionId !== null) {
            $this->mpSubscriptionId = $mpSubscriptionId;
        }
        $this->touch();
    }

    public function extendPeriod(\DateTimeImmutable $periodEnd): void
    {
        $this->currentPeriodEnd = $periodEnd;
        $this->status = SubscriptionStatus::Active;
        $this->touch();
    }

    public function markPastDue(): void
    {
        $this->status = SubscriptionStatus::PastDue;
        $this->touch();
    }

    public function cancel(): void
    {
        $this->cancelAtPeriodEnd = true;
        $this->touch();
    }

    public function markCancelled(): void
    {
        $this->status = SubscriptionStatus::Cancelled;
        $this->touch();
    }

    public function hasActiveBenefit(): bool
    {
        if (!$this->status->isActiveBenefit()) {
            return false;
        }

        return $this->currentPeriodEnd !== null && $this->currentPeriodEnd > new \DateTimeImmutable();
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
