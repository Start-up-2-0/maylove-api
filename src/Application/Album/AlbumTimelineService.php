<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMemory;
use App\Infrastructure\Persistence\Repository\AlbumMemoryRepository;

final class AlbumTimelineService
{
    public function __construct(
        private readonly AlbumMemoryRepository $memoryRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Album $album, ?string $from = null, ?string $to = null): array
    {
        $memories = $this->memoryRepository->findOrderedByAlbum($album);

        $items = [];
        foreach ($memories as $memory) {
            $date = $memory->getMemoryDate();
            if ($date === null) {
                continue;
            }
            $iso = $date->format('Y-m-d');
            if (($from !== null && $iso < $from) || ($to !== null && $iso > $to)) {
                continue;
            }

            /** @var AlbumMemory $memory */
            $items[] = [
                'memory_id' => (string) $memory->getId(),
                'date' => $iso,
                'time' => $memory->getMemoryTime()?->format('H:i'),
                'title' => $memory->getTitle(),
                'sentiment' => $memory->getSentiment()?->value,
                'location_label' => $memory->getLocationLabel(),
                'has_media' => true,
            ];
        }

        return [
            'items' => $items,
            'total' => \count($items),
        ];
    }
}
