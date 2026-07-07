<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use Predis\Client;

final class MediaPresignRateLimitService
{
    public function __construct(
        private readonly Client $redis,
    ) {
    }

    public function canPresign(string $tributeId): bool
    {
        $attempts = (int) $this->redis->get($this->key($tributeId));

        return $attempts < 30;
    }

    public function registerPresign(string $tributeId): void
    {
        $key = $this->key($tributeId);
        $attempts = $this->redis->incr($key);

        if ($attempts === 1) {
            $this->redis->expire($key, 3600);
        }
    }

    private function key(string $tributeId): string
    {
        return 'maylove:media_presign:'.$tributeId;
    }
}
