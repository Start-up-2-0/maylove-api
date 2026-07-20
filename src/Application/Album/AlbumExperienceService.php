<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\Exception\AlbumException;
use App\Domain\Album\ExperienceType;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumExperience;
use App\Infrastructure\Persistence\Entity\AlbumMemory;
use App\Infrastructure\Persistence\Repository\AlbumExperienceRepository;
use App\Infrastructure\Persistence\Repository\AlbumMemoryRepository;
use Symfony\Component\Uid\Uuid;

final class AlbumExperienceService
{
    public function __construct(
        private readonly AlbumExperienceRepository $experienceRepository,
        private readonly AlbumMemoryRepository $memoryRepository,
    ) {
    }

    /**
     * @return list<AlbumExperience>
     */
    public function listByAlbum(Album $album): array
    {
        return $this->experienceRepository->findOrderedByAlbum($album);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(Album $album, array $payload): AlbumExperience
    {
        $type = ExperienceType::tryFrom((string) ($payload['type'] ?? ''));
        if ($type === null) {
            throw new AlbumException('Tipo de experiência inválido.', 'validation_error', 422);
        }

        if (!is_array($payload['config'] ?? null)) {
            throw new AlbumException('A configuração da experiência é obter.', 'validation_error', 422);
        }

        $memory = null;
        if (!empty($payload['memory_id'])) {
            $memory = $this->memoryRepository->findByIdAndAlbum(Uuid::fromString((string) $payload['memory_id']), $album);
            if ($memory === null) {
                throw new AlbumException('Memória não encontrada.', 'memory_not_found', 404);
            }
        }

        /** @var array<string, mixed> $config */
        $config = $payload['config'];

        $experience = new AlbumExperience(
            $album,
            $type,
            $config,
            $memory,
        );
        $this->experienceRepository->save($experience);

        return $experience;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Album $album, string $id, array $payload): AlbumExperience
    {
        $experience = $this->findOwned($album, $id);

        if (isset($payload['type'])) {
            $type = ExperienceType::tryFrom((string) $payload['type']);
            if ($type === null) {
                throw new AlbumException('Tipo de experiência inválido.', 'validation_error', 422);
            }
            $experience->setType($type);
        }
        if (isset($payload['config']) && is_array($payload['config'])) {
            /** @var array<string, mixed> $config */
            $config = $payload['config'];
            $experience->setConfigJson($config);
        }

        $this->experienceRepository->save($experience);

        return $experience;
    }

    public function delete(Album $album, string $id): void
    {
        $experience = $this->findOwned($album, $id);
        $em = $this->experienceRepository->getEntityManager();
        $em->remove($experience);
        $em->flush();
    }

    public function findOwned(Album $album, string $id): AlbumExperience
    {
        if (!Uuid::isValid($id)) {
            throw new AlbumException('Experiência não encontrada.', 'experience_not_found', 404);
        }

        $experience = $this->experienceRepository->findByIdAndAlbum(Uuid::fromString($id), $album);
        if ($experience === null) {
            throw new AlbumException('Experiência não encontrada.', 'experience_not_found', 404);
        }

        return $experience;
    }
}
