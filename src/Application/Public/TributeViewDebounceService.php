<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Infrastructure\RateLimit\InMemoryRateLimiter;

final class TributeViewDebounceService
{
    private const DEBOUNCE_SECONDS = 1800;

    public function __construct(
        private readonly InMemoryRateLimiter $rateLimiter,
    ) {
    }

    public function shouldRecord(string $tributeId, string $sessionId): bool
    {
        return $this->rateLimiter->claim($this->key($tributeId, $sessionId), self::DEBOUNCE_SECONDS);
    }

    private function key(string $tributeId, string $sessionId): string
    {
        return 'maylove:view:'.$tributeId.':'.$sessionId;
    }
}
