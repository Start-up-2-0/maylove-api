<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Application\Album\AlbumMusicConfig;
use App\Domain\Album\AlbumMediaType;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
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
        $audioMedia = null;
        foreach ($media as $item) {
            if ($item->getMediaType() === AlbumMediaType::Audio) {
                $audioMedia = $item;
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
            'book_config' => $album->getBookConfig(),
            'book_pages' => $album->getBookPages(),
            'published_at' => $album->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'views_count' => $album->getViewsCount(),
            'photos' => array_map(fn (AlbumMedia $item) => $this->serializeMedia($item), $photos),
            'music' => $this->serializeMusic($album, $audioMedia),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function serializeMusic(Album $album, ?AlbumMedia $audioMedia): ?array
    {
        $musicCfg = AlbumMusicConfig::get($album);
        if (($musicCfg['source'] ?? 'none') === 'none' && $audioMedia === null) {
            return null;
        }

        if ($audioMedia === null) {
            return null;
        }

        $base = $this->serializeMedia($audioMedia);

        return [
            ...$base,
            'autoplay' => (bool) ($musicCfg['autoplay'] ?? true),
            'loop' => (bool) ($musicCfg['loop'] ?? true),
            'duration_seconds' => isset($musicCfg['duration_seconds']) ? (float) $musicCfg['duration_seconds'] : null,
            'start_seconds' => isset($musicCfg['start_seconds']) ? (float) $musicCfg['start_seconds'] : 0.0,
            'end_seconds' => isset($musicCfg['end_seconds']) ? (float) $musicCfg['end_seconds'] : null,
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
