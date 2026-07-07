<?php

declare(strict_types=1);

namespace App\Application\Public;

use Predis\Client;

final class TributeViewDebounceService
{
    private const DEBOUNCE_SECONDS = 1800;

    public function __construct(
        private readonly Client $redis,
    ) {
    }

    public function shouldRecord(string $tributeId, string $sessionId): bool
    {
        $key = $this->key($tributeId, $sessionId);
        if ($this->redis->exists($key) > 0) {
            return false;
        }

        $this->redis->setex($key, self::DEBOUNCE_SECONDS, '1');

        return true;
    }

    private function key(string $tributeId, string $sessionId): string
    {
        return 'maylove:view:'.$tributeId.':'.$sessionId;
    }
}
