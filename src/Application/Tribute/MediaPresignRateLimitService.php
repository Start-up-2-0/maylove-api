<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Infrastructure\RateLimit\DbRateLimiter;

final class MediaPresignRateLimitService
{
    public function __construct(
        private readonly DbRateLimiter $rateLimiter,
    ) {
    }

    public function canPresign(string $tributeId): bool
    {
        return $this->rateLimiter->get($this->key($tributeId)) < 30;
    }

    public function registerPresign(string $tributeId): void
    {
        $this->rateLimiter->increment($this->key($tributeId), 3600);
    }

    private function key(string $tributeId): string
    {
        return 'maylove:media_presign:'.$tributeId;
    }
}
