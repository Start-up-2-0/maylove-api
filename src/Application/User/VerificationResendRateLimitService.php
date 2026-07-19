<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Infrastructure\RateLimit\DbRateLimiter;

final class VerificationResendRateLimitService
{
    public function __construct(
        private readonly DbRateLimiter $rateLimiter,
        private readonly int $maxPerHour,
    ) {
    }

    public function canResend(string $userId): bool
    {
        return $this->rateLimiter->get($this->key($userId)) < $this->maxPerHour;
    }

    public function registerResend(string $userId): void
    {
        $this->rateLimiter->increment($this->key($userId), 3600);
    }

    private function key(string $userId): string
    {
        return 'maylove:verify_resend:'.$userId;
    }
}
