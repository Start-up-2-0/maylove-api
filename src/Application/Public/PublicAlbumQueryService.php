<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Repository\AlbumRepository;

final class PublicAlbumQueryService
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {
    }

    public function getPublishedBySlug(string $slug): Album
    {
        $album = $this->albumRepository->findPublishedBySlug($slug);
        if ($album === null) {
            throw new AlbumException('Álbum não encontrado.', 'ALBUM_NOT_FOUND', 404);
        }

        return $album;
    }
}
