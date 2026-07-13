<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Repository\AlbumRepository;
use Symfony\Component\Uid\Uuid;

final class AlbumViewService
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly PublicAlbumQueryService $queryService,
    ) {
    }

    public function recordView(string $slug, string $sessionId): void
    {
        if (trim($sessionId) === '') {
            throw new AlbumException('session_id é obrigatório.', 'VALIDATION_ERROR', 400);
        }

        $album = $this->queryService->getPublishedBySlug($slug);
        $this->albumRepository->incrementViewsCount(Uuid::fromString((string) $album->getId()));
    }
}
