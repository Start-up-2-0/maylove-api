<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Album\AlbumMediaType;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
use App\Infrastructure\Persistence\Repository\AlbumRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class PublicAlbumSerializer
{
    private const MEDIA_URL_TTL = 86400;

    public function __construct(
        private readonly AlbumMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Album $album): array
    {
        $media = $this->mediaRepository->findActiveByAlbum($album);
        $photos = array_values(array_filter(
            $media,
            fn (AlbumMedia $item) => $item->getMediaType() === AlbumMediaType::Photo,
        ));
        $audio = null;
        foreach ($media as $item) {
            if ($item->getMediaType() === AlbumMediaType::Audio) {
                $audio = $this->serializeMedia($item);
                break;
            }
        }

        return [
            'slug' => $album->getSlug(),
            'title' => $album->getTitle(),
            'subtitle' => $album->getSubtitle(),
            'closing_message' => $album->getClosingMessage(),
            'signature' => $album->getSignature(),
            'color_primary' => $album->getColorPrimary(),
            'presentation' => $album->getPresentation()->value,
            'photos_per_page' => $album->getPhotosPerPage(),
            'published_at' => $album->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'views_count' => $album->getViewsCount(),
            'photos' => array_map(fn (AlbumMedia $item) => $this->serializeMedia($item), $photos),
            'music' => $audio,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMedia(AlbumMedia $media): array
    {
        $url = null;
        try {
            $urlData = $this->storageApiClient->getFileUrl(
                $media->getStorageFileId(),
                'protected',
                self::MEDIA_URL_TTL,
            );
            $url = isset($urlData['url']) ? (string) $urlData['url'] : null;
        } catch (\Throwable) {
        }

        return [
            'id' => (string) $media->getId(),
            'url' => $url,
            'title' => $media->getTitle(),
            'caption' => $media->getCaption(),
            'memory_date' => $media->getMemoryDate(),
            'sort_order' => $media->getSortOrder(),
        ];
    }
}
