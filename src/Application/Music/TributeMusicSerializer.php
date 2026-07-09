<?php

declare(strict_types=1);

namespace App\Application\Music;

use App\Domain\Tribute\MediaProcessingStatus;
use App\Domain\Tribute\MusicSource;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\TributeMedia;
use App\Infrastructure\Persistence\Repository\Music\MusicTrackRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class TributeMusicSerializer
{
    private const MEDIA_URL_TTL = 86400;

    public function __construct(
        private readonly MusicTrackRepository $musicTrackRepository,
        private readonly MusicTrackSerializer $musicTrackSerializer,
        private readonly StorageApiClient $storageApiClient,
    ) {
    }

    /**
     * @param list<TributeMedia>|null $media
     *
     * @return array<string, mixed>
     */
    public function serialize(Tribute $tribute, ?array $media = null): array
    {
        $source = $tribute->getMusicSource();
        $block = [
            'source' => $source->value,
            'track_id' => $tribute->getMusicTrackId() !== null ? (string) $tribute->getMusicTrackId() : null,
            'media_id' => $tribute->getMusicMediaId() !== null ? (string) $tribute->getMusicMediaId() : null,
            'track' => null,
            'url' => null,
            'preview_url' => null,
        ];

        if ($source === MusicSource::Library && $tribute->getMusicTrackId() !== null) {
            $track = $this->musicTrackRepository->findActiveById($tribute->getMusicTrackId());
            if ($track !== null) {
                $serialized = $this->musicTrackSerializer->serialize($track);
                $block['track'] = $serialized;
                $block['url'] = $serialized['file_url'];
                $block['preview_url'] = $serialized['preview_url'];
            }
        }

        if ($source === MusicSource::Upload && $tribute->getMusicMediaId() !== null && $media !== null) {
            foreach ($media as $item) {
                if (
                    $item->getId()->equals($tribute->getMusicMediaId())
                    && $item->getProcessingStatus() === MediaProcessingStatus::Ready
                ) {
                    $url = $this->resolveMediaUrl($item);
                    $block['url'] = $url;
                    $block['preview_url'] = $url;
                    break;
                }
            }
        }

        $content = $tribute->getContentJson();
        if (isset($content['music_duration_seconds'])) {
            $block['duration_seconds'] = (float) $content['music_duration_seconds'];
        } elseif ($block['track'] !== null) {
            $block['duration_seconds'] = $block['track']['duration_seconds'] ?? null;
        }

        return $block;
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
