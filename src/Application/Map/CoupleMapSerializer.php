<?php

declare(strict_types=1);

namespace App\Application\Map;

use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\MapPlace;
use App\Infrastructure\Persistence\Entity\MapPlaceMedia;
use App\Infrastructure\Persistence\Repository\MapPlaceMediaRepository;
use App\Infrastructure\Persistence\Repository\MapPlaceRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class CoupleMapSerializer
{
    private const MEDIA_URL_TTL = 3600;
    private const MEDIA_URL_TTL_MARGIN_SECONDS = 60;

    public function __construct(
        private readonly MapPlaceRepository $placeRepository,
        private readonly MapPlaceMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
    ) {
    }

    /** @return array<string, mixed> */
    public function serializeSummary(CoupleMap $map): array
    {
        $places = $this->placeRepository->findActiveByMap($map);
        $highlight = null;
        foreach ($places as $place) {
            if ($place->isHighlight()) {
                $highlight = $place;
                break;
            }
        }

        return [
            'id' => (string) $map->getId(),
            'slug' => $map->getSlug(),
            'status' => $map->getStatus()->value,
            'title' => $map->getTitle(),
            'subtitle' => $map->getSubtitle(),
            'couple_names' => $map->getCoupleNames(),
            'map_style' => $map->getMapStyle()->value,
            'show_route' => $map->isShowRoute(),
            'places_count' => count($places),
            'views_count' => $map->getViewsCount(),
            'highlight_title' => $highlight?->getTitle(),
            'published_at' => $map->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $map->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $map->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function serializeDetail(CoupleMap $map, bool $includePlaceMedia = true): array
    {
        $data = $this->serializeSummary($map);
        $data['center_lat'] = $map->getCenterLat();
        $data['center_lng'] = $map->getCenterLng();
        $data['default_zoom'] = $map->getDefaultZoom();
        $data['content_json'] = $map->getContentJson();
        $data['media_urls_expire_at'] = (new \DateTimeImmutable())
            ->modify(sprintf('+%d seconds', self::MEDIA_URL_TTL - self::MEDIA_URL_TTL_MARGIN_SECONDS))
            ->format(\DateTimeInterface::ATOM);

        $places = $this->placeRepository->findActiveByMap($map);
        $data['places'] = array_map(
            fn (MapPlace $place) => $this->serializePlace($place, $includePlaceMedia),
            $places,
        );

        return $data;
    }

    /** @return array<string, mixed> */
    public function serializePlace(MapPlace $place, bool $includeMedia = false): array
    {
        $payload = [
            'id' => (string) $place->getId(),
            'place_type' => $place->getPlaceType()->value,
            'title' => $place->getTitle(),
            'subtitle' => $place->getSubtitle(),
            'description' => $place->getDescription(),
            'memory_date' => $place->getMemoryDate()?->format('Y-m-d'),
            'memory_time' => $place->getMemoryTime()?->format('H:i:s'),
            'latitude' => $place->getLatitude(),
            'longitude' => $place->getLongitude(),
            'address_label' => $place->getAddressLabel(),
            'city' => $place->getCity(),
            'country' => $place->getCountry(),
            'sentiment' => $place->getSentiment(),
            'is_highlight' => $place->isHighlight(),
            'sort_order' => $place->getSortOrder(),
            'content_json' => $place->getContentJson(),
            'created_at' => $place->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $place->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($includeMedia) {
            $media = $this->mediaRepository->findActiveByPlace($place);
            $payload['media'] = array_map(
                fn (MapPlaceMedia $item) => $this->serializeMedia($item, includeUrl: true),
                $media,
            );
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    public function serializeMedia(MapPlaceMedia $media, bool $includeUrl = false): array
    {
        $payload = [
            'id' => (string) $media->getId(),
            'place_id' => (string) $media->getPlace()->getId(),
            'storage_file_id' => $media->getStorageFileId(),
            'media_type' => $media->getMediaType()->value,
            'original_filename' => $media->getOriginalFilename(),
            'mime_type' => $media->getMimeType(),
            'size_bytes' => $media->getSizeBytes(),
            'sort_order' => $media->getSortOrder(),
            'url_thumbnail' => $media->getUrlThumbnail(),
            'created_at' => $media->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];

        if ($includeUrl) {
            $payload['url'] = $this->resolveMediaUrl($media);
        }

        return $payload;
    }

    private function resolveMediaUrl(MapPlaceMedia $media): ?string
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
