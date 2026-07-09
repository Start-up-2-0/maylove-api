<?php

declare(strict_types=1);

namespace App\Application\Music;

use App\Domain\Shared\UuidValue;
use App\Domain\Tribute\Exception\TributeException;
use App\Domain\Tribute\MediaProcessingStatus;
use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\TributeMedia;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class TributeMusicStatusService
{
    private const MEDIA_URL_TTL = 86400;

    public function __construct(
        private readonly TributeMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveForTribute(Tribute $tribute): array
    {
        $content = $tribute->getContentJson();
        $import = $content['music_import'] ?? null;
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

        $mediaId = $tribute->getMusicMediaId();
        if ($mediaId === null) {
            return [
                'media_id' => null,
                'processing_status' => 'ready',
                'processing_error' => null,
                'duration_seconds' => null,
                'url' => null,
            ];
        }

        $media = $this->mediaRepository->findActiveByIdAndTribute($mediaId, $tribute);
        if ($media === null) {
            throw new TributeException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        return $this->serializeMedia($media, $content);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveForMedia(Tribute $tribute, string $mediaId): array
    {
        if (!UuidValue::isValid($mediaId)) {
            throw new TributeException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        $media = $this->mediaRepository->findActiveByIdAndTribute(UuidValue::fromString($mediaId), $tribute);
        if ($media === null) {
            throw new TributeException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        return $this->serializeMedia($media, $tribute->getContentJson());
    }

    /**
     * @param array<string, mixed> $content
     *
     * @return array<string, mixed>
     */
    private function serializeMedia(TributeMedia $media, array $content): array
    {
        $status = $media->getProcessingStatus();
        $duration = isset($content['music_duration_seconds']) ? (float) $content['music_duration_seconds'] : null;
        $url = null;

        if ($status === MediaProcessingStatus::Ready && $media->getMimeType() !== null) {
            $url = $this->resolveMediaUrl($media);
        }

        return [
            'media_id' => (string) $media->getId(),
            'processing_status' => $status->value,
            'processing_error' => $media->getProcessingError(),
            'duration_seconds' => $duration,
            'url' => $url,
        ];
    }

    private function resolveMediaUrl(TributeMedia $media): ?string
    {
        if ($media->getMediaType() !== TributeMediaType::Audio || $media->getMimeType() === null) {
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
