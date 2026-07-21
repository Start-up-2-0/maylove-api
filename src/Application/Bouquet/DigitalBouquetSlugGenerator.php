<?php

declare(strict_types=1);

namespace App\Application\Bouquet;

use App\Infrastructure\Persistence\Repository\DigitalBouquetRepository;
use Symfony\Component\Uid\Uuid;

final class DigitalBouquetSlugGenerator
{
    public function __construct(
        private readonly DigitalBouquetRepository $bouquetRepository,
    ) {
    }

    public function generateUnique(): string
    {
        do {
            $candidate = $this->truncate('bouquet-'.Uuid::v4()->toRfc4122());
        } while ($this->bouquetRepository->existsBySlug($candidate));

        return $candidate;
    }

    private function truncate(string $slug): string
    {
        return substr($slug, 0, 100);
    }
}
