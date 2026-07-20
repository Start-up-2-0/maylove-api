<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Map\Exception\CoupleMapException;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Repository\CoupleMapRepository;

final class PublicCoupleMapQueryService
{
    public function __construct(
        private readonly CoupleMapRepository $mapRepository,
    ) {
    }

    public function getPublishedBySlug(string $slug): CoupleMap
    {
        $map = $this->mapRepository->findPublishedBySlug($slug);
        if ($map === null) {
            throw new CoupleMapException('Mapa não encontrado.', 'MAP_NOT_FOUND', 404);
        }

        return $map;
    }
}
