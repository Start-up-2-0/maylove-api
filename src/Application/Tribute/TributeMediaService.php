<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Application\Billing\PendingOrderInvalidationService;
use App\Domain\Tribute\Exception\TributeException;
use App\Domain\Tribute\MusicSource;
use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\TributeMedia;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;
use App\Infrastructure\Storage\StorageApiClient;
use Symfony\Component\Uid\Uuid;

final class TributeMediaService
{
    private const PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const VIDEO_MIMES = ['video/mp4'];
    private const AUDIO_MIMES = ['audio/mpeg', 'audio/mp3'];

    private const MAX_PHOTO_BYTES = 10 * 1024 * 1024;
    private const MAX_VIDEO_BYTES = 50 * 1024 * 1024;
    private const MAX_AUDIO_BYTES = 15 * 1024 * 1024;

    public function __construct(
        private readonly TributeMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly MediaPresignRateLimitService $rateLimitService,
        private readonly TributeSerializer $serializer,
        private readonly PendingOrderInvalidationService $pendingOrderInvalidation,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function presign(Tribute $tribute, User $user, array $payload): array
    {
        $this->assertEditable($tribute);

        $tributeId = (string) $tribute->getId();
        if (!$this->rateLimitService->canPresign($tributeId)) {
            throw new TributeException('Limite de uploads atingido. Tente novamente em breve.', 'RATE_LIMIT', 429);
        }

        $mediaType = TributeMediaType::tryFrom((string) ($payload['media_type'] ?? ''));
        if ($mediaType === null) {
            throw new TributeException('Tipo de mídia inválido.', 'VALIDATION_ERROR', 400);
        }

        $filename = basename(str_replace('\\', '/', (string) ($payload['filename'] ?? '')));
        $mimeType = strtolower(trim((string) ($payload['mime_type'] ?? '')));
        $sizeBytes = (int) ($payload['size_bytes'] ?? 0);

        if ($filename === '' || str_contains($filename, '..')) {
            throw new TributeException('Nome de arquivo inválido.', 'INVALID_FILENAME', 400);
        }

        $this->assertMimeAndSize($mediaType, $mimeType, $sizeBytes);
        $this->assertQuota($tribute, $mediaType);

        if ($mediaType === TributeMediaType::Audio) {
            $tribute->setMusicSource(MusicSource::Upload);
            $tribute->setMusicTrackId(null);
        }

        $ticket = $this->storageApiClient->createUploadTicket([
            'context' => 'tribute',
            'context_id' => $tributeId,
            'media_type' => $mediaType->value,
            'original_filename' => $filename,
            'mime_type' => $mimeType,
            'max_size_bytes' => $this->maxBytesFor($mediaType),
            'ttl_seconds' => 900,
            'user_id' => (string) $user->getId(),
        ]);

        $sortOrder = $this->mediaRepository->getNextSortOrder($tribute);
        $media = new TributeMedia(
            $tribute,
            (string) ($ticket['file_id'] ?? ''),
            $mediaType,
            $filename,
            $sortOrder,
        );
        $media->setMimeType($mimeType);
        $media->setSizeBytes($sizeBytes > 0 ? $sizeBytes : null);

        $this->mediaRepository->save($media);
        $this->rateLimitService->registerPresign($tributeId);

        return [
            'media_id' => (string) $media->getId(),
            'storage_file_id' => (string) ($ticket['file_id'] ?? ''),
            'upload_url' => (string) ($ticket['upload_url'] ?? ''),
            'upload_ticket' => (string) ($ticket['upload_ticket'] ?? ''),
            'expires_at' => (string) ($ticket['expires_at'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function confirm(Tribute $tribute, string $mediaId): array
    {
        $this->assertEditable($tribute);

        if (!Uuid::isValid($mediaId)) {
            throw new TributeException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        $media = $this->mediaRepository->findActiveByIdAndTribute(Uuid::fromString($mediaId), $tribute);
        if ($media === null) {
            throw new TributeException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        $confirmed = $this->storageApiClient->confirmFile(
            $media->getStorageFileId(),
            $media->getMediaType()->value,
            (string) ($media->getMimeType() ?? 'application/octet-stream'),
        );

        $media->setMimeType(isset($confirmed['mime_type']) ? (string) $confirmed['mime_type'] : $media->getMimeType());
        $media->setSizeBytes(isset($confirmed['size_bytes']) ? (int) $confirmed['size_bytes'] : $media->getSizeBytes());
        $thumbnail = $confirmed['thumbnail_url'] ?? null;
        $media->setUrlThumbnail(is_string($thumbnail) ? $thumbnail : null);

        if ($media->getMediaType() === TributeMediaType::Audio) {
            $tribute->setMusicSource(MusicSource::Upload);
            $tribute->setMusicMediaId($media->getId());
            $tribute->setMusicTrackId(null);
        }

        $this->mediaRepository->save($media);

        return $this->serializer->serializeMedia($media, includeUrl: true);
    }

    public function delete(Tribute $tribute, string $mediaId): void
    {
        $this->assertEditable($tribute);

        if (!Uuid::isValid($mediaId)) {
            throw new TributeException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        $media = $this->mediaRepository->findActiveByIdAndTribute(Uuid::fromString($mediaId), $tribute);
        if ($media === null) {
            throw new TributeException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        try {
            $this->storageApiClient->deleteFile($media->getStorageFileId());
        } catch (\Throwable) {
            // Storages pode já ter removido; segue soft delete local.
        }

        if ($tribute->getMusicMediaId()?->equals($media->getId())) {
            $tribute->setMusicMediaId(null);
            if ($tribute->getMusicSource() === MusicSource::Upload) {
                $tribute->setMusicSource(MusicSource::None);
            }
        }

        $media->softDelete();
        $this->mediaRepository->save($media);
    }

    /**
     * @param list<string> $order
     */
    public function reorder(Tribute $tribute, array $order): void
    {
        $this->assertEditable($tribute);

        if ($order === []) {
            throw new TributeException('Ordem de mídias inválida.', 'VALIDATION_ERROR', 400);
        }

        $activeMedia = $this->mediaRepository->findActiveByTribute($tribute);
        $byId = [];
        foreach ($activeMedia as $media) {
            $byId[(string) $media->getId()] = $media;
        }

        if (count($order) !== count($byId)) {
            throw new TributeException('Lista de ordenação incompleta.', 'VALIDATION_ERROR', 400);
        }

        $sortOrder = 0;
        foreach ($order as $mediaId) {
            if (!isset($byId[$mediaId])) {
                throw new TributeException('Mídia inválida na ordenação.', 'VALIDATION_ERROR', 400);
            }
            $byId[$mediaId]->setSortOrder($sortOrder++);
        }

        $this->mediaRepository->flush();
    }

    private function assertEditable(Tribute $tribute): void
    {
        $this->pendingOrderInvalidation->invalidateIfAwaitingPayment($tribute);

        if (!$tribute->isEditable()) {
            throw new TributeException(
                'Upload não permitido para esta homenagem.',
                'TRIBUTE_NOT_EDITABLE',
                403,
            );
        }
    }

    private function assertMimeAndSize(TributeMediaType $type, string $mimeType, int $sizeBytes): void
    {
        $allowed = match ($type) {
            TributeMediaType::Photo => self::PHOTO_MIMES,
            TributeMediaType::Video => self::VIDEO_MIMES,
            TributeMediaType::Audio => self::AUDIO_MIMES,
        };

        if (!in_array($mimeType, $allowed, true)) {
            throw new TributeException('Tipo MIME não permitido.', 'INVALID_MIME', 422);
        }

        if ($sizeBytes <= 0) {
            throw new TributeException('Tamanho do arquivo é obrigatório.', 'VALIDATION_ERROR', 400);
        }

        if ($sizeBytes > $this->maxBytesFor($type)) {
            throw new TributeException('Arquivo excede o tamanho máximo.', 'FILE_TOO_LARGE', 422);
        }
    }

    private function assertQuota(Tribute $tribute, TributeMediaType $type): void
    {
        if ($type === TributeMediaType::Photo) {
            $count = $this->mediaRepository->countActiveByTributeAndType($tribute, TributeMediaType::Photo);
            $max = $tribute->getTemplate()->getMaxPhotos();
            if ($count >= $max) {
                throw new TributeException('Limite de fotos atingido.', 'PHOTO_QUOTA_EXCEEDED', 422);
            }

            return;
        }

        if ($type === TributeMediaType::Video) {
            $count = $this->mediaRepository->countActiveByTributeAndType($tribute, TributeMediaType::Video);
            if ($count >= 1) {
                throw new TributeException('Apenas um vídeo por homenagem.', 'VIDEO_QUOTA_EXCEEDED', 422);
            }
            if (!$tribute->getTemplate()->supportsVideo()) {
                throw new TributeException('Template não suporta vídeo.', 'VIDEO_NOT_SUPPORTED', 422);
            }

            return;
        }

        $count = $this->mediaRepository->countActiveByTributeAndType($tribute, TributeMediaType::Audio);
        if ($count >= 1) {
            throw new TributeException('Apenas um áudio por homenagem.', 'AUDIO_QUOTA_EXCEEDED', 422);
        }
        if (!$tribute->getTemplate()->supportsMusic()) {
            throw new TributeException('Template não suporta música.', 'MUSIC_NOT_SUPPORTED', 422);
        }
    }

    private function maxBytesFor(TributeMediaType $type): int
    {
        return match ($type) {
            TributeMediaType::Photo => self::MAX_PHOTO_BYTES,
            TributeMediaType::Video => self::MAX_VIDEO_BYTES,
            TributeMediaType::Audio => self::MAX_AUDIO_BYTES,
        };
    }
}
