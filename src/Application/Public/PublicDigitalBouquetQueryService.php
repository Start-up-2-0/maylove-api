<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Bouquet\Exception\DigitalBouquetException;
use App\Infrastructure\Persistence\Entity\DigitalBouquet;
use App\Infrastructure\Persistence\Repository\DigitalBouquetRepository;

final class PublicDigitalBouquetQueryService
{
    public function __construct(
        private readonly DigitalBouquetRepository $bouquetRepository,
    ) {
    }

    public function getPublishedBySlug(string $slug): DigitalBouquet
    {
        $bouquet = $this->bouquetRepository->findPublishedBySlug($slug);
        if ($bouquet === null) {
            throw new DigitalBouquetException('Buquê não encontrado.', 'BOUQUET_NOT_FOUND', 404);
        }

        return $bouquet;
    }
}
