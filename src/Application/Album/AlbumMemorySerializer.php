<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Infrastructure\Persistence\Entity\AlbumMemory;

final class AlbumMemorySerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(AlbumMemory $memory): array
    {
        return [
            'id' => (string) $memory->getId(),
            'sort_order' => $memory->getSortOrder(),
            'title' => $memory->getTitle(),
            'subtitle' => $memory->getSubtitle(),
            'description' => $memory->getDescription(),
            'date' => $memory->getMemoryDate()?->format('Y-m-d'),
            'time' => $memory->getMemoryTime()?->format('H:i'),
            'location' => [
                'lat' => $memory->getLocationLat(),
                'lng' => $memory->getLocationLng(),
                'label' => $memory->getLocationLabel(),
            ],
            'climate' => $memory->getClimate(),
            'sentiment' => $memory->getSentiment()?->value,
            'tags' => $memory->getTags(),
            'people' => $memory->getPeople(),
            'content_json' => $memory->getContentJson(),
            'created_at' => $memory->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $memory->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param list<AlbumMemory> $memories
     * @return list<array<string, mixed>>
     */
    public function serializeList(array $memories): array
    {
        return array_map($this->serialize(...), $memories);
    }
}
