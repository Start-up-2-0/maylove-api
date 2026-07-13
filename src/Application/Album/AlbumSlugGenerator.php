<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Infrastructure\Persistence\Repository\AlbumRepository;
use Symfony\Component\Uid\Uuid;

final class AlbumSlugGenerator
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {
    }

    public function generateUnique(): string
    {
        do {
            $candidate = $this->truncate('album-'.Uuid::v4()->toRfc4122());
        } while ($this->albumRepository->existsBySlug($candidate));

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
