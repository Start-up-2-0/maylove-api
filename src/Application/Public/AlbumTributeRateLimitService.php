<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Infrastructure\RateLimit\DbRateLimiter;

final class AlbumTributeRateLimitService
{
    private const WINDOW_SECONDS = 3600;
    private const MAX_PER_WINDOW = 8;

    public function __construct(
        private readonly DbRateLimiter $rateLimiter,
    ) {
    }

    public function canSubmit(string $albumSlug, string $ipHash): bool
    {
        $key = sprintf('album_tribute:%s:%s', $albumSlug, $ipHash);
        $count = $this->rateLimiter->increment($key, self::WINDOW_SECONDS);

        return $count <= self::MAX_PER_WINDOW;
    }
}
