<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Infrastructure\Persistence\Entity\AlbumChapter;

final class AlbumChapterSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(AlbumChapter $chapter): array
    {
        return [
            'id' => (string) $chapter->getId(),
            'title' => $chapter->getTitle(),
            'sort_order' => $chapter->getSortOrder(),
            'created_at' => $chapter->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $chapter->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param list<AlbumChapter> $chapters
     * @return list<array<string, mixed>>
     */
    public function serializeList(array $chapters): array
    {
        return array_map($this->serialize(...), $chapters);
    }
}
