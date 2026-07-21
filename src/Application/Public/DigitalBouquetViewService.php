<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Bouquet\Exception\DigitalBouquetException;
use App\Infrastructure\Persistence\Repository\DigitalBouquetRepository;

final class DigitalBouquetViewService
{
    public function __construct(
        private readonly PublicDigitalBouquetQueryService $queryService,
        private readonly DigitalBouquetRepository $bouquetRepository,
    ) {
    }

    public function recordView(string $slug, string $sessionId): void
    {
        $bouquet = $this->queryService->getPublishedBySlug($slug);
        if ($sessionId === '') {
            throw new DigitalBouquetException('Sessão inválida.', 'INVALID_SESSION', 422);
        }

        $this->bouquetRepository->incrementViewsCount($bouquet->getId());
    }
}
