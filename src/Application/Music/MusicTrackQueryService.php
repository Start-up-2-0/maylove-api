<?php

declare(strict_types=1);

namespace App\Application\Music;

use App\Infrastructure\Persistence\Entity\Music\MusicTrack;
use App\Infrastructure\Persistence\Repository\Music\MusicTrackRepository;
use Symfony\Component\Uid\Uuid;

final class MusicTrackQueryService
{
    public function __construct(
        private readonly MusicTrackRepository $musicTrackRepository,
        private readonly MusicTrackSerializer $serializer,
    ) {
    }

    /**
     * @return array{tracks: list<array<string, mixed>>, categories: list<string>}
     */
    public function list(?string $category = null): array
    {
        $tracks = $this->musicTrackRepository->findAllActive($category);

        return [
            'tracks' => array_map(
                fn (MusicTrack $track) => $this->serializer->serialize($track),
                $tracks,
            ),
            'categories' => $this->musicTrackRepository->findActiveCategories(),
        ];
    }

    public function getActiveById(string $id): ?MusicTrack
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->musicTrackRepository->findActiveById(Uuid::fromString($id));
    }
}
