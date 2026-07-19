<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Infrastructure\RateLimit\DbRateLimiter;

final class LoginRateLimitService
{
    private const MAX_ATTEMPTS = 10;
    private const WINDOW_SECONDS = 900;

    public function __construct(
        private readonly DbRateLimiter $rateLimiter,
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
