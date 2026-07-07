<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Tribute\Exception\TributeException;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Repository\TributeRepository;

final class PublicTributeQueryService
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
    ) {
    }

    public function getPublishedBySlug(string $slug): Tribute
    {
        $slug = trim($slug);
        if ($slug === '') {
            throw new TributeException('Homenagem não encontrada.', 'TRIBUTE_NOT_FOUND', 404);
        }

        $tribute = $this->tributeRepository->findPublishedBySlug($slug);
        if ($tribute === null) {
            throw new TributeException('Homenagem não encontrada.', 'TRIBUTE_NOT_FOUND', 404);
        }

        return $tribute;
    }
}
