<?php

declare(strict_types=1);

namespace App\Application\Auth;

use Predis\Client;

final class LoginRateLimitService
{
    private const MAX_ATTEMPTS = 20;
    private const WINDOW_SECONDS = 900;

    public function __construct(
        private readonly Client $redis,
    ) {
    }

    public function canAttempt(string $ip): bool
    {
        $key = $this->key($ip);
        $attempts = (int) $this->redis->get($key);

        return $attempts < self::MAX_ATTEMPTS;
    }

    public function registerFailure(string $ip): void
    {
        $key = $this->key($ip);
        $attempts = $this->redis->incr($key);

        if ($attempts === 1) {
            $this->redis->expire($key, self::WINDOW_SECONDS);
        }
    }

    private function key(string $ip): string
    {
        return 'maylove:login_fail:'.sha1($ip);
    }
}
