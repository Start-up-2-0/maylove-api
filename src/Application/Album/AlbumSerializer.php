<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\AlbumMediaType;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
use App\Infrastructure\Storage\StorageApiClient;
use Symfony\Component\Uid\Uuid;

final class AlbumSerializer
{
    private const MEDIA_URL_TTL = 3600;
    private const MEDIA_URL_TTL_MARGIN_SECONDS = 60;

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
        $gallery = $this->resolveGalleryPreview($album);

        return [
            'id' => (string) $album->getId(),
            'slug' => $album->getSlug(),
            'status' => $album->getStatus()->value,
            'title' => $album->getTitle(),
            'subtitle' => $album->getSubtitle(),
            'color_primary' => $album->getColorPrimary(),
            'presentation' => $album->getPresentation()->value,
            'photos_per_page' => $album->getPhotosPerPage(),
            'is_public' => $album->isPublic(),
            'photo_count' => $this->mediaRepository->countActiveByAlbumAndType($album, AlbumMediaType::Photo),
            'views_count' => $album->getViewsCount(),
            'cover_url' => $gallery['cover_url'],
            'preview_thumbs' => $gallery['preview_thumbs'],
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
        $data['book_config'] = $album->getBookConfig();
        $data['book_pages'] = $album->getBookPages();
        $data['media_urls_expire_at'] = (new \DateTimeImmutable())
            ->modify(sprintf('+%d seconds', self::MEDIA_URL_TTL - self::MEDIA_URL_TTL_MARGIN_SECONDS))
            ->format(\DateTimeInterface::ATOM);
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
            'place_name' => $media->getPlaceName(),
            'url_thumbnail' => $media->getUrlThumbnail(),
            'created_at' => $media->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($includeUrl) {
            $payload['url'] = $this->resolveMediaUrl($media);
        }

        return $payload;
    }

    /**
     * @return array{cover_url: ?string, preview_thumbs: list<string>}
     */
    private function resolveGalleryPreview(Album $album): array
    {
        $photos = $this->mediaRepository->findActivePhotosByAlbum($album, 8);
        if ($photos === []) {
            return ['cover_url' => null, 'preview_thumbs' => []];
        }

        $coverId = null;
        $config = $album->getBookConfig();
        if (is_array($config) && isset($config['cover']) && is_array($config['cover'])) {
            $raw = $config['cover']['media_id'] ?? null;
            if (is_string($raw) && $raw !== '' && Uuid::isValid($raw)) {
                $coverId = $raw;
            }
        }

        $coverMedia = null;
        foreach ($photos as $photo) {
            if ($coverId !== null && (string) $photo->getId() === $coverId) {
                $coverMedia = $photo;
                break;
            }
        }
        $coverMedia ??= $photos[0];

        $coverUrl = $this->resolveMediaUrl($coverMedia) ?? $coverMedia->getUrlThumbnail();
        $thumbs = [];
        foreach ($photos as $photo) {
            if ((string) $photo->getId() === (string) $coverMedia->getId()) {
                continue;
            }
            $url = $photo->getUrlThumbnail() ?? $this->resolveMediaUrl($photo);
            if ($url === null || $url === '') {
                continue;
            }
            $thumbs[] = $url;
            if (count($thumbs) >= 3) {
                break;
            }
        }

        return [
            'cover_url' => $coverUrl,
            'preview_thumbs' => $thumbs,
        ];
    }

    private function resolveMediaUrl(AlbumMedia $media): ?string
    {
        try {
            $urlData = $this->storageApiClient->getFileUrl(
                $media->getStorageFileId(),
                'protected',
                self::MEDIA_URL_TTL,
            );

            return isset($urlData['url']) ? (string) $urlData['url'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
