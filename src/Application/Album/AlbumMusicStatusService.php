<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\AlbumMediaType;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class AlbumMusicStatusService
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
    public function resolveForAlbum(Album $album): array
    {
        $music = AlbumMusicConfig::get($album);
        $import = $music['import'] ?? null;

        if (is_array($import) && ($import['status'] ?? '') === 'processing') {
            return [
                'media_id' => isset($import['media_id']) ? (string) $import['media_id'] : null,
                'processing_status' => 'processing',
                'processing_error' => null,
                'duration_seconds' => null,
                'url' => null,
            ];
        }

        if (is_array($import) && ($import['status'] ?? '') === 'failed') {
            return [
                'media_id' => isset($import['media_id']) ? (string) $import['media_id'] : null,
                'processing_status' => 'failed',
                'processing_error' => isset($import['error']) ? (string) $import['error'] : 'Falha na importação.',
                'duration_seconds' => null,
                'url' => null,
            ];
        }

        $mediaId = $album->getMusicMediaId();
        if ($mediaId === null) {
            return [
                'media_id' => null,
                'processing_status' => 'ready',
                'processing_error' => null,
                'duration_seconds' => null,
                'url' => null,
            ];
        }

        $media = $this->mediaRepository->findActiveByIdAndAlbum($mediaId, $album);
        if ($media === null) {
            throw new AlbumException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        return $this->serializeMedia($media, $music);
    }

    /**
     * @param array<string, mixed> $music
     *
     * @return array<string, mixed>
     */
    private function serializeMedia(AlbumMedia $media, array $music): array
    {
        $duration = isset($music['duration_seconds']) ? (float) $music['duration_seconds'] : null;
        $url = null;

        if ($media->getMediaType() === AlbumMediaType::Audio && $media->getMimeType() !== null) {
            $url = $this->resolveMediaUrl($media);
        }

        return [
            'media_id' => (string) $media->getId(),
            'processing_status' => 'ready',
            'processing_error' => null,
            'duration_seconds' => $duration,
            'url' => $url,
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
