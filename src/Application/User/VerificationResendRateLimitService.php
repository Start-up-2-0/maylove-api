<?php

declare(strict_types=1);

namespace App\Application\User;

use Predis\Client;

final class VerificationResendRateLimitService
{
    public function __construct(
        private readonly Client $redis,
        private readonly int $maxPerHour,
    ) {
    }

    public function canResend(string $userId): bool
    {
        $attempts = (int) $this->redis->get($this->key($userId));

        return $attempts < $this->maxPerHour;
    }

    public function registerResend(string $userId): void
    {
        $key = $this->key($userId);
        $attempts = $this->redis->incr($key);

        if ($attempts === 1) {
            $this->redis->expire($key, 3600);
        }
    }

    private function key(string $userId): string
    {
        return 'maylove:verify_resend:'.$userId;
    }
}
