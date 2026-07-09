<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Infrastructure\RateLimit\InMemoryRateLimiter;

final class LoginRateLimitService
{
    private const MAX_ATTEMPTS = 20;
    private const WINDOW_SECONDS = 900;

    public function __construct(
        private readonly InMemoryRateLimiter $rateLimiter,
    ) {
    }

    public function canAttempt(string $ip): bool
    {
        return $this->rateLimiter->get($this->key($ip)) < self::MAX_ATTEMPTS;
    }

    public function registerFailure(string $ip): void
    {
        $this->rateLimiter->increment($this->key($ip), self::WINDOW_SECONDS);
    }

    private function key(string $ip): string
    {
        return 'maylove:login_fail:'.sha1($ip);
    }
}
