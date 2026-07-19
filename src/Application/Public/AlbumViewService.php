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
        private readonly ViewRateLimitService $rateLimitService,
    ) {
    }

    public function recordView(string $slug, string $sessionId): void
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '' || !Uuid::isValid($sessionId)) {
            throw new AlbumException('session_id inválido.', 'VALIDATION_ERROR', 400);
        }

        if (!$this->rateLimitService->canRecordView($slug, $sessionId)) {
            return;
        }

        $album = $this->queryService->getPublishedBySlug($slug);
        $this->albumRepository->incrementViewsCount(Uuid::fromString((string) $album->getId()));
    }
}
