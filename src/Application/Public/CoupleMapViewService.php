<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Infrastructure\Persistence\Repository\CoupleMapRepository;

final class CoupleMapViewService
{
    public function __construct(
        private readonly CoupleMapRepository $mapRepository,
        private readonly PublicCoupleMapQueryService $queryService,
    ) {
    }

    public function recordView(string $slug, string $sessionId): void
    {
        if ($sessionId === '') {
            return;
        }

        $map = $this->queryService->getPublishedBySlug($slug);
        $this->mapRepository->incrementViewsCount($map->getId());
    }
}
