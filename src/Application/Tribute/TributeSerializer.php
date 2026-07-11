<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Application\Music\TributeMusicSerializer;
use App\Domain\Tribute\MediaProcessingStatus;
use App\Domain\Tribute\MusicSource;
use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\TributeMedia;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;
use App\Infrastructure\Persistence\Repository\TributeViewRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class TributeSerializer
{
    public function __construct(
        private readonly TributeMediaRepository $mediaRepository,
        private readonly TributeViewRepository $tributeViewRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly TributeMusicSerializer $tributeMusicSerializer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeSummary(Tribute $tribute): array
    {
        return [
            'id' => (string) $tribute->getId(),
            'slug' => $tribute->getSlug(),
            'status' => $tribute->getStatus()->value,
            'title' => $tribute->getTitle(),
            'honoree_name' => $tribute->getHonoreeName(),
            'tribute_type' => [
                'id' => (string) $tribute->getTributeType()->getId(),
                'slug' => $tribute->getTributeType()->getSlug(),
                'name' => $tribute->getTributeType()->getName(),
            ],
            'template' => [
                'id' => (string) $tribute->getTemplate()->getId(),
                'slug' => $tribute->getTemplate()->getSlug(),
                'name' => $tribute->getTemplate()->getName(),
            ],
            'color_primary' => $tribute->getColorPrimary(),
            'presentation' => $this->resolvePresentation($tribute),
            'views_count' => $tribute->getViewsCount(),
            'published_at' => $tribute->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $tribute->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $tribute->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeDetail(Tribute $tribute, bool $includeTemplateConfig = false): array
    {
        $data = $this->serializeSummary($tribute);
        $data['subtitle'] = $tribute->getSubtitle();
        $data['message'] = $tribute->getMessage();
        $data['closing_message'] = $tribute->getClosingMessage();
        $data['special_date'] = $tribute->getSpecialDate()?->format('Y-m-d');
        $data['music_source'] = $tribute->getMusicSource()->value;
        $data['music_track_id'] = $tribute->getMusicTrackId() !== null ? (string) $tribute->getMusicTrackId() : null;
        $data['music_media_id'] = $tribute->getMusicMediaId() !== null ? (string) $tribute->getMusicMediaId() : null;
        $media = $this->mediaRepository->findActiveByTribute($tribute);
        $data['music'] = $this->tributeMusicSerializer->serialize($tribute, $media);
        $data['content_json'] = $tribute->getContentJson();
        $data['og_image_url'] = $tribute->getOgImageUrl();
        $data['billing_mode_used'] = $tribute->getBillingModeUsed()?->value;
        $data['media'] = array_map(
            fn (TributeMedia $item) => $this->serializeMedia($item, includeUrl: true),
            $media,
        );

        $templateData = [
            'id' => (string) $tribute->getTemplate()->getId(),
            'slug' => $tribute->getTemplate()->getSlug(),
            'name' => $tribute->getTemplate()->getName(),
            'primary_color' => $tribute->getTemplate()->getPrimaryColor(),
            'max_photos' => $tribute->getTemplate()->getMaxPhotos(),
            'supports_video' => $tribute->getTemplate()->supportsVideo(),
            'supports_music' => $tribute->getTemplate()->supportsMusic(),
            'has_animation' => $tribute->getTemplate()->hasAnimation(),
        ];

        if ($includeTemplateConfig) {
            $templateData['config_json'] = $tribute->getTemplate()->getConfigJson();
        }

        $data['template'] = $templateData;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMedia(TributeMedia $media, bool $includeUrl = false): array
    {
        $data = [
            'id' => (string) $media->getId(),
            'storage_file_id' => $media->getStorageFileId(),
            'media_type' => $media->getMediaType()->value,
            'original_filename' => $media->getOriginalFilename(),
            'mime_type' => $media->getMimeType(),
            'size_bytes' => $media->getSizeBytes(),
            'sort_order' => $media->getSortOrder(),
            'url_thumbnail' => $media->getUrlThumbnail(),
            'created_at' => $media->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'processing_status' => $media->getProcessingStatus()->value,
            'processing_error' => $media->getProcessingError(),
        ];

        if (
            $includeUrl
            && $media->getMimeType() !== null
            && $media->getProcessingStatus() === MediaProcessingStatus::Ready
        ) {
            try {
                $urlData = $this->storageApiClient->getFileUrl($media->getStorageFileId());
                $data['url'] = $urlData['url'] ?? null;
            } catch (\Throwable) {
                $data['url'] = null;
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeStats(Tribute $tribute): array
    {
        $photos = $this->mediaRepository->countActiveByTributeAndType($tribute, TributeMediaType::Photo);
        $videos = $this->mediaRepository->countActiveByTributeAndType($tribute, TributeMediaType::Video);
        $viewBounds = $this->tributeViewRepository->getViewedAtBoundsForTribute($tribute);

        return [
            'status' => $tribute->getStatus()->value,
            'views_count' => $tribute->getViewsCount(),
            'first_viewed_at' => $viewBounds['first_viewed_at']?->format(\DateTimeInterface::ATOM),
            'last_viewed_at' => $viewBounds['last_viewed_at']?->format(\DateTimeInterface::ATOM),
            'photos_count' => $photos,
            'has_video' => $videos > 0,
            'has_music' => $tribute->getMusicSource() !== MusicSource::None,
            'published_at' => $tribute->getPublishedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function resolvePresentation(Tribute $tribute): ?string
    {
        $content = $tribute->getContentJson();
        $presentation = $content['presentation'] ?? null;

        return is_string($presentation) && $presentation !== '' ? $presentation : null;
    }
}
