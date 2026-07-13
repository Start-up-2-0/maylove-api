<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\AlbumMediaType;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class AlbumSerializer
{
    private const MEDIA_URL_TTL = 3600;

    public function __construct(
        private readonly AlbumMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeSummary(Album $album): array
    {
        return [
            'id' => (string) $album->getId(),
            'slug' => $album->getSlug(),
            'status' => $album->getStatus()->value,
            'title' => $album->getTitle(),
            'subtitle' => $album->getSubtitle(),
            'color_primary' => $album->getColorPrimary(),
            'presentation' => $album->getPresentation()->value,
            'is_public' => $album->isPublic(),
            'photo_count' => $this->mediaRepository->countActiveByAlbumAndType($album, AlbumMediaType::Photo),
            'views_count' => $album->getViewsCount(),
            'published_at' => $album->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $album->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $album->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeDetail(Album $album): array
    {
        $data = $this->serializeSummary($album);
        $data['closing_message'] = $album->getClosingMessage();
        $data['signature'] = $album->getSignature();
        $data['music_media_id'] = $album->getMusicMediaId() !== null
            ? (string) $album->getMusicMediaId()
            : null;
        $media = $this->mediaRepository->findActiveByAlbum($album);
        $data['media'] = array_map(
            fn (AlbumMedia $item) => $this->serializeMedia($item, includeUrl: true),
            $media,
        );

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMedia(AlbumMedia $media, bool $includeUrl = false): array
    {
        $payload = [
            'id' => (string) $media->getId(),
            'storage_file_id' => $media->getStorageFileId(),
            'media_type' => $media->getMediaType()->value,
            'original_filename' => $media->getOriginalFilename(),
            'mime_type' => $media->getMimeType(),
            'size_bytes' => $media->getSizeBytes(),
            'sort_order' => $media->getSortOrder(),
            'title' => $media->getTitle(),
            'caption' => $media->getCaption(),
            'memory_date' => $media->getMemoryDate(),
            'url_thumbnail' => $media->getUrlThumbnail(),
            'created_at' => $media->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($includeUrl) {
            try {
                $urlData = $this->storageApiClient->getFileUrl(
                    $media->getStorageFileId(),
                    'protected',
                    self::MEDIA_URL_TTL,
                );
                $payload['url'] = isset($urlData['url']) ? (string) $urlData['url'] : null;
            } catch (\Throwable) {
                $payload['url'] = null;
            }
        }

        return $payload;
    }
}
