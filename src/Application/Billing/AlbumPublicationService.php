<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Repository\AlbumRepository;

final class AlbumPublicationService
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
    ) {
    }

    public function publishFromPayment(Album $album): void
    {
        if ($album->getStatus()->isPublic()) {
            return;
        }

        $album->markPublished();
        $this->albumRepository->save($album);
    }
}
