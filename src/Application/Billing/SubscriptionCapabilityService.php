<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Tribute\BillingModeUsed;
use App\Infrastructure\Persistence\Entity\Billing\UserSubscription;
use App\Infrastructure\Persistence\Repository\Billing\UserSubscriptionRepository;
use Symfony\Component\Uid\Uuid;

final class SubscriptionCapabilityService
{
    public function __construct(
        private readonly UserSubscriptionRepository $subscriptionRepository,
        private readonly bool $billingEnabled,
    ) {
    }

    public function isBillingEnabled(): bool
    {
        return $this->billingEnabled;
    }

    public function hasActiveSubscription(string $userId): bool
    {
        return $this->resolveActiveSubscription($userId) !== null;
    }

    public function canPublishFree(string $userId): bool
    {
        if (!$this->billingEnabled) {
            return true;
        }

        return $this->hasActiveSubscription($userId);
    }

    public function getBillingMode(string $userId): ?BillingModeUsed
    {
        $subscription = $this->resolveActiveSubscription($userId);
        if ($subscription === null) {
            return null;
        }

        return $subscription->getProductPrice()->getBillingMode()->toBillingModeUsed();
    }

    public function invalidateCache(string $userId): void
    {
        // Sem cache externo no MVP.
    }

    private function resolveActiveSubscription(string $userId): ?UserSubscription
    {
        if (!Uuid::isValid($userId)) {
            return null;
        }

        $subscription = $this->subscriptionRepository->findByUserId(Uuid::fromString($userId));
        if ($subscription === null || !$subscription->hasActiveBenefit()) {
            return null;
        }

        return $subscription;
    }
}
