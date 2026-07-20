<?php

declare(strict_types=1);

namespace App\Application\Map;

use App\Infrastructure\Persistence\Repository\CoupleMapRepository;
use Symfony\Component\Uid\Uuid;

final class CoupleMapSlugGenerator
{
    public function __construct(
        private readonly CoupleMapRepository $mapRepository,
    ) {
    }

    public function generateUnique(): string
    {
        do {
            $candidate = $this->truncate('map-'.Uuid::v4()->toRfc4122());
        } while ($this->mapRepository->existsBySlug($candidate));

        return $candidate;
    }

    public function isValidFormat(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    private function truncate(string $slug): string
    {
        return substr($slug, 0, 100);
    }
}
