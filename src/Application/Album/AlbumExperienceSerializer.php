<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Infrastructure\Persistence\Entity\AlbumExperience;

final class AlbumExperienceSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(AlbumExperience $experience): array
    {
        return [
            'id' => (string) $experience->getId(),
            'type' => $experience->getType()->value,
            'memory_id' => $experience->getMemory() !== null ? (string) $experience->getMemory()->getId() : null,
            'config' => $experience->getConfigJson(),
            'sort_order' => $experience->getSortOrder(),
            'created_at' => $experience->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $experience->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param list<AlbumExperience> $experiences
     * @return list<array<string, mixed>>
     */
    public function serializeList(array $experiences): array
    {
        return array_map($this->serialize(...), $experiences);
    }
}
