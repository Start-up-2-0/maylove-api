<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

/**
 * Rate limit simples em memória do processo (adequado para MVP / instância única).
 */
final class InMemoryRateLimiter
{
    /** @var array<string, array{count: int, expiresAt: int}> */
    private array $entries = [];

    public function get(string $key): int
    {
        $this->purgeExpired();

        return $this->entries[$key]['count'] ?? 0;
    }

    public function increment(string $key, int $windowSeconds): int
    {
        $this->purgeExpired();
        $now = time();

        if (!isset($this->entries[$key]) || $this->entries[$key]['expiresAt'] <= $now) {
            $this->entries[$key] = ['count' => 1, 'expiresAt' => $now + $windowSeconds];

            return 1;
        }

        $this->entries[$key]['count']++;

        return $this->entries[$key]['count'];
    }

    public function claim(string $key, int $ttlSeconds): bool
    {
        $this->purgeExpired();
        $now = time();

        if (isset($this->entries[$key]) && $this->entries[$key]['expiresAt'] > $now) {
            return false;
        }

        $this->entries[$key] = ['count' => 1, 'expiresAt' => $now + $ttlSeconds];

        return true;
    }

    private function purgeExpired(): void
    {
        $now = time();

        foreach ($this->entries as $key => $entry) {
            if ($entry['expiresAt'] <= $now) {
                unset($this->entries[$key]);
            }
        }
    }
}
