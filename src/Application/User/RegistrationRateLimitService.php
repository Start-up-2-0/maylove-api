<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Infrastructure\RateLimit\DbRateLimiter;

final class RegistrationRateLimitService
{
    private const MAX_PER_WINDOW = 5;
    private const WINDOW_SECONDS = 3600; // 1 hora

    public function __construct(
        private readonly DbRateLimiter $rateLimiter,
    ) {
    }

    public function canRegister(string $ip): bool
    {
        return $this->rateLimiter->get($this->key($ip)) < self::MAX_PER_WINDOW;
    }

    public function registerAttempt(string $ip): void
    {
        $this->rateLimiter->increment($this->key($ip), self::WINDOW_SECONDS);
    }

    private function key(string $ip): string
    {
        return 'maylove:register:'.sha1($ip);
    }
}
