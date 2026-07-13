<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\AlbumStatus;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Repository\AlbumRepository;

final class AlbumPublishService
{
    public function __construct(
        private readonly AlbumValidationService $validationService,
        private readonly AlbumRepository $albumRepository,
    ) {
    }

    public function publish(Album $album): Album
    {
        if ($album->getStatus() === AlbumStatus::Published) {
            throw new AlbumException('Álbum já publicado.', 'ALREADY_PUBLISHED', 409);
        }

        if (!$album->isEditable()) {
            throw new AlbumException('Álbum não pode ser publicado.', 'ALBUM_NOT_EDITABLE', 403);
        }

        $validation = $this->validationService->validate($album);
        if (!$validation['valid']) {
            throw new AlbumException('Álbum incompleto para publicação.', 'VALIDATION_FAILED', 422);
        }

        $album->markPublished();
        $this->albumRepository->save($album);

        return $album;
    }
}
