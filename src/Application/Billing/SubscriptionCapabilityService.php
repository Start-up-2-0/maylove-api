<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\ProductBillingMode;
use App\Domain\Tribute\BillingModeUsed;
use App\Infrastructure\Persistence\Entity\Billing\UserSubscription;
use App\Infrastructure\Persistence\Repository\Billing\UserSubscriptionRepository;
use Predis\Client;
use Symfony\Component\Uid\Uuid;

final class SubscriptionCapabilityService
{
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly UserSubscriptionRepository $subscriptionRepository,
        private readonly Client $redis,
    ) {
    }

    public function hasActiveSubscription(string $userId): bool
    {
        return $this->resolveActiveSubscription($userId) !== null;
    }

    public function canPublishFree(string $userId): bool
    {
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
        $this->redis->del($this->cacheKey($userId));
    }

    private function resolveActiveSubscription(string $userId): ?UserSubscription
    {
        if (!Uuid::isValid($userId)) {
            return null;
        }

        $cacheKey = $this->cacheKey($userId);
        $cached = $this->redis->get($cacheKey);
        if ($cached === '0') {
            return null;
        }
        if (is_string($cached) && $cached !== '' && $cached !== '0') {
            $subscription = $this->subscriptionRepository->findByUserId(Uuid::fromString($userId));
            if ($subscription !== null && (string) $subscription->getId() === $cached && $subscription->hasActiveBenefit()) {
                return $subscription;
            }
        }

        $subscription = $this->subscriptionRepository->findByUserId(Uuid::fromString($userId));
        if ($subscription === null || !$subscription->hasActiveBenefit()) {
            $this->redis->setex($cacheKey, self::CACHE_TTL, '0');

            return null;
        }

        $this->redis->setex($cacheKey, self::CACHE_TTL, (string) $subscription->getId());

        return $subscription;
    }

    private function cacheKey(string $userId): string
    {
        return 'subscription:active:'.$userId;
    }
}
