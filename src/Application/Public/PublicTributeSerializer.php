<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Application\Music\TributeMusicSerializer;
use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\TributeMedia;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class PublicTributeSerializer
{
    private const MEDIA_URL_TTL = 86400;

    public function __construct(
        private readonly TributeMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly TributeMusicSerializer $tributeMusicSerializer,
        private readonly PublicTributeContentFilter $contentFilter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Tribute $tribute): array
    {
        $media = $this->mediaRepository->findActiveByTribute($tribute);
        $serializedMedia = array_map(
            fn (TributeMedia $item) => $this->serializeMedia($item),
            $media,
        );

        return [
            'slug' => $tribute->getSlug(),
            'title' => $tribute->getTitle(),
            'subtitle' => $tribute->getSubtitle(),
            'honoree_name' => $tribute->getHonoreeName(),
            'message' => $tribute->getMessage(),
            'closing_message' => $tribute->getClosingMessage(),
            'special_date' => $tribute->getSpecialDate()?->format('Y-m-d'),
            'color_primary' => $tribute->getColorPrimary(),
            'content_json' => $this->contentFilter->filter($tribute->getContentJson()),
            'og_image_url' => $this->resolveOgImageUrl($tribute, $media),
            'published_at' => $tribute->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'views_count' => $tribute->getViewsCount(),
            'tribute_type' => [
                'slug' => $tribute->getTributeType()->getSlug(),
                'name' => $tribute->getTributeType()->getName(),
                'icon' => $tribute->getTributeType()->getIcon(),
            ],
            'template' => [
                'slug' => $tribute->getTemplate()->getSlug(),
                'name' => $tribute->getTemplate()->getName(),
                'primary_color' => $tribute->getTemplate()->getPrimaryColor(),
                'supports_video' => $tribute->getTemplate()->supportsVideo(),
                'supports_music' => $tribute->getTemplate()->supportsMusic(),
                'has_animation' => $tribute->getTemplate()->hasAnimation(),
                'config_json' => $tribute->getTemplate()->getConfigJson(),
            ],
            'music' => $this->tributeMusicSerializer->serialize($tribute, $media),
            'media' => $serializedMedia,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMedia(TributeMedia $media): array
    {
        $url = $this->resolveMediaUrl($media);

        return [
            'id' => (string) $media->getId(),
            'media_type' => $media->getMediaType()->value,
            'mime_type' => $media->getMimeType(),
            'sort_order' => $media->getSortOrder(),
            'url' => $url,
            'url_thumbnail' => $media->getUrlThumbnail() ?? $url,
        ];
    }

    /**
     * @param list<TributeMedia> $media
     */
    private function resolveOgImageUrl(Tribute $tribute, array $media): ?string
    {
        if ($tribute->getOgImageUrl() !== null && $tribute->getOgImageUrl() !== '') {
            return $tribute->getOgImageUrl();
        }

        foreach ($media as $item) {
            if ($item->getMediaType() === TributeMediaType::Photo) {
                return $item->getUrlThumbnail() ?? $this->resolveMediaUrl($item);
            }
        }

        return null;
    }

    private function resolveMediaUrl(TributeMedia $media): ?string
    {
        if ($media->getMimeType() === null) {
            return null;
        }

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
