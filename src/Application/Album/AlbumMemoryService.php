<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\Exception\AlbumException;
use App\Domain\Album\Sentiment;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumChapter;
use App\Infrastructure\Persistence\Entity\AlbumMemory;
use App\Infrastructure\Persistence\Repository\AlbumMemoryRepository;
use Symfony\Component\Uid\Uuid;

final class AlbumMemoryService
{
    public function __construct(
        private readonly AlbumMemoryRepository $memoryRepository,
    ) {
    }

    /**
     * @return list<AlbumMemory>
     */
    public function listByChapter(AlbumChapter $chapter): array
    {
        return $this->memoryRepository->findOrderedByChapter($chapter);
    }

    public function create(AlbumChapter $chapter, Album $album, array $payload): AlbumMemory
    {
        $sortOrder = is_numeric($payload['sort_order'] ?? null)
            ? (int) $payload['sort_order']
            : $this->memoryRepository->getNextSortOrder($chapter);

        $memory = new AlbumMemory($chapter, $album, max(0, $sortOrder));
        $this->apply($memory, $payload);
        $this->memoryRepository->save($memory);

        return $memory;
    }

    public function update(Album $album, string $id, array $payload): AlbumMemory
    {
        $memory = $this->findOwned($album, $id);
        $this->apply($memory, $payload);
        $this->memoryRepository->save($memory);

        return $memory;
    }

    public function delete(Album $album, string $id): void
    {
        $memory = $this->findOwned($album, $id);
        $em = $this->memoryRepository->getEntityManager();
        $em->remove($memory);
        $em->flush();
    }

    public function findOwned(Album $album, string $id): AlbumMemory
    {
        if (!Uuid::isValid($id)) {
            throw new AlbumException('Memória não encontrada.', 'memory_not_found', 404);
        }

        $memory = $this->memoryRepository->findByIdAndAlbum(Uuid::fromString($id), $album);
        if ($memory === null) {
            throw new AlbumException('Memória não encontrada.', 'memory_not_found', 404);
        }

        return $memory;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function apply(AlbumMemory $memory, array $payload): void
    {
        if (array_key_exists('title', $payload)) {
            $memory->setTitle(isset($payload['title']) ? (string) $payload['title'] : null);
        }
        if (array_key_exists('subtitle', $payload)) {
            $memory->setSubtitle(isset($payload['subtitle']) ? (string) $payload['subtitle'] : null);
        }
        if (array_key_exists('description', $payload)) {
            $memory->setDescription(isset($payload['description']) ? (string) $payload['description'] : null);
        }
        if (array_key_exists('date', $payload) && $payload['date'] !== null && $payload['date'] !== '') {
            $memory->setMemoryDate(new \DateTimeImmutable((string) $payload['date']));
        }
        if (array_key_exists('time', $payload) && $payload['time'] !== null && $payload['time'] !== '') {
            $memory->setMemoryTime(new \DateTimeImmutable('1970-01-01 '.(string) $payload['time']));
        }
        if (array_key_exists('location', $payload) && is_array($payload['location'])) {
            $loc = $payload['location'];
            $memory->setLocationLat(isset($loc['lat']) ? (string) $loc['lat'] : null);
            $memory->setLocationLng(isset($loc['lng']) ? (string) $loc['lng'] : null);
            $memory->setLocationLabel(isset($loc['label']) ? (string) $loc['label'] : null);
        }
        if (array_key_exists('climate', $payload)) {
            $memory->setClimate(isset($payload['climate']) ? (string) $payload['climate'] : null);
        }
        if (array_key_exists('sentiment', $payload) && $payload['sentiment'] !== null && $payload['sentiment'] !== '') {
            $sentiment = Sentiment::tryFrom((string) $payload['sentiment']);
            if ($sentiment === null) {
                throw new AlbumException('Sentimento inválido.', 'validation_error', 422);
            }
            $memory->setSentiment($sentiment);
        }
        if (array_key_exists('tags', $payload) && is_array($payload['tags'])) {
            /** @var list<string> $tags */
            $tags = array_map('strval', $payload['tags']);
            $memory->setTags($tags);
        }
        if (array_key_exists('people', $payload) && is_array($payload['people'])) {
            /** @var list<string> $people */
            $people = array_map('strval', $payload['people']);
            $memory->setPeople($people);
        }
        if (array_key_exists('content_json', $payload) && is_array($payload['content_json'])) {
            /** @var array<string, mixed> $content */
            $content = $payload['content_json'];
            $memory->setContentJson($content);
        }
        if (array_key_exists('sort_order', $payload) && is_numeric($payload['sort_order'])) {
            $memory->setSortOrder(max(0, (int) $payload['sort_order']));
        }
    }
}
