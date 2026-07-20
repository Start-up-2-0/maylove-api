<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumChapter;
use App\Infrastructure\Persistence\Repository\AlbumChapterRepository;
use Symfony\Component\Uid\Uuid;

final class AlbumChapterService
{
    public function __construct(
        private readonly AlbumChapterRepository $chapterRepository,
    ) {
    }

    /**
     * @return list<AlbumChapter>
     */
    public function listByAlbum(Album $album): array
    {
        return $this->chapterRepository->findOrderedByAlbum($album);
    }

    public function create(Album $album, array $payload): AlbumChapter
    {
        $title = isset($payload['title']) ? trim((string) $payload['title']) : '';
        if ($title === '') {
            throw new AlbumException('O título do capítulo é obrigatório.', 'validation_error', 422);
        }

        $sortOrder = is_numeric($payload['sort_order'] ?? null)
            ? (int) $payload['sort_order']
            : $this->chapterRepository->getNextSortOrder($album);

        $chapter = new AlbumChapter($album, $title, max(0, $sortOrder));
        $this->chapterRepository->save($chapter);

        return $chapter;
    }

    public function update(Album $album, string $id, array $payload): AlbumChapter
    {
        $chapter = $this->findOwned($album, $id);

        if (isset($payload['title'])) {
            $title = trim((string) $payload['title']);
            if ($title === '') {
                throw new AlbumException('O título do capítulo é obrigatório.', 'validation_error', 422);
            }
            $chapter->setTitle($title);
        }

        if (isset($payload['sort_order']) && is_numeric($payload['sort_order'])) {
            $chapter->setSortOrder(max(0, (int) $payload['sort_order']));
        }

        $this->chapterRepository->save($chapter);

        return $chapter;
    }

    public function delete(Album $album, string $id): void
    {
        $chapter = $this->findOwned($album, $id);
        $em = $this->chapterRepository->getEntityManager();
        $em->remove($chapter);
        $em->flush();
    }

    public function findOwned(Album $album, string $id): AlbumChapter
    {
        if (!Uuid::isValid($id)) {
            throw new AlbumException('Capítulo não encontrado.', 'chapter_not_found', 404);
        }

        $chapter = $this->chapterRepository->findByIdAndAlbum(Uuid::fromString($id), $album);
        if ($chapter === null) {
            throw new AlbumException('Capítulo não encontrado.', 'chapter_not_found', 404);
        }

        return $chapter;
    }
}
