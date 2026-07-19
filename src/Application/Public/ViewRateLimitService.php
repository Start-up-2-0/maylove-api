<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Infrastructure\RateLimit\DbRateLimiter;

final class ViewRateLimitService
{
    private const WINDOW_SECONDS = 60;
    private const MAX_VIEWS_PER_WINDOW = 30;

    public function __construct(
        private readonly DbRateLimiter $rateLimiter,
    ) {
    }

    public function canRecordView(string $slug, string $sessionId): bool
    {
        $key = sprintf('view:%s:%s', $slug, $sessionId);
        $count = $this->rateLimiter->increment($key, self::WINDOW_SECONDS);

        return $count <= self::MAX_VIEWS_PER_WINDOW;
    }
}
