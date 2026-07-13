<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Application\Media\MediaUploadPolicy;
use App\Application\Tribute\MediaPresignRateLimitService;
use App\Domain\Album\AlbumMediaType;
use App\Domain\Album\Exception\AlbumException;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
use App\Infrastructure\Persistence\Repository\AlbumRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class AlbumMediaService
{
    public function __construct(
        private readonly AlbumMediaRepository $mediaRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly MediaPresignRateLimitService $rateLimitService,
        private readonly AlbumSerializer $serializer,
        private readonly MediaUploadPolicy $uploadPolicy,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function presign(Album $album, User $user, array $payload): array
    {
        $this->assertEditable($album);

        $albumId = (string) $album->getId();
        if (!$this->rateLimitService->canPresign($albumId)) {
            throw new AlbumException('Limite de uploads atingido. Tente novamente em breve.', 'RATE_LIMIT', 429);
        }

        $mediaType = AlbumMediaType::tryFrom((string) ($payload['media_type'] ?? ''));
        if ($mediaType === null) {
            throw new AlbumException('Tipo de mídia inválido.', 'VALIDATION_ERROR', 400);
        }

        $filename = basename(str_replace('\\', '/', (string) ($payload['filename'] ?? '')));
        $mimeType = $this->resolveMimeType(
            strtolower(trim((string) ($payload['mime_type'] ?? ''))),
            $filename,
            $mediaType,
        );
        $sizeBytes = (int) ($payload['size_bytes'] ?? 0);

        if ($filename === '' || str_contains($filename, '..')) {
            throw new AlbumException('Nome de arquivo inválido.', 'INVALID_FILENAME', 400);
        }

        $this->assertMimeAndSize($mediaType, $mimeType, $sizeBytes);
        $this->assertQuota($album, $mediaType);

        $ticket = $this->storageApiClient->createUploadTicket([
            'context' => 'album',
            'context_id' => $albumId,
            'media_type' => $mediaType->value,
            'original_filename' => $filename,
            'mime_type' => $mimeType,
            'max_size_bytes' => $this->maxBytesFor($mediaType),
            'ttl_seconds' => 900,
            'user_id' => (string) $user->getId(),
        ]);

        $sortOrder = $this->mediaRepository->getNextSortOrder($album);
        $media = new AlbumMedia(
            $album,
            (string) ($ticket['file_id'] ?? ''),
            $mediaType,
            $filename,
            $sortOrder,
        );
        $media->setMimeType($mimeType);
        $media->setSizeBytes($sizeBytes > 0 ? $sizeBytes : null);

        $this->mediaRepository->save($media);
        $this->rateLimitService->registerPresign($albumId);

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
    public function confirm(Album $album, string $mediaId): array
    {
        $this->assertEditable($album);

        if (!UuidValue::isValid($mediaId)) {
            throw new AlbumException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        $media = $this->mediaRepository->findActiveByIdAndAlbum(UuidValue::fromString($mediaId), $album);
        if ($media === null) {
            throw new AlbumException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        $confirmed = $this->storageApiClient->confirmFile(
            $media->getStorageFileId(),
            $media->getMediaType()->value,
            (string) ($media->getMimeType() ?? 'application/octet-stream'),
        );

        $mimeType = isset($confirmed['mime_type']) ? (string) $confirmed['mime_type'] : (string) $media->getMimeType();
        $media->setMimeType($mimeType);
        $media->setSizeBytes(isset($confirmed['size_bytes']) ? (int) $confirmed['size_bytes'] : $media->getSizeBytes());
        $thumbnail = $confirmed['thumbnail_url'] ?? null;
        $media->setUrlThumbnail(is_string($thumbnail) ? $thumbnail : null);

        if ($media->getMediaType() === AlbumMediaType::Audio) {
            $album->setMusicMediaId($media->getId());
            $this->albumRepository->save($album);
        }

        $this->mediaRepository->save($media);

        return $this->serializer->serializeMedia($media, includeUrl: true);
    }

    public function delete(Album $album, string $mediaId): void
    {
        $this->assertEditable($album);

        if (!UuidValue::isValid($mediaId)) {
            throw new AlbumException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        $media = $this->mediaRepository->findActiveByIdAndAlbum(UuidValue::fromString($mediaId), $album);
        if ($media === null) {
            throw new AlbumException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        try {
            $this->storageApiClient->deleteFile($media->getStorageFileId());
        } catch (\Throwable) {
        }

        if ($album->getMusicMediaId()?->equals($media->getId())) {
            $album->setMusicMediaId(null);
            $this->albumRepository->save($album);
        }

        $media->softDelete();
        $this->mediaRepository->save($media);
    }

    /**
     * @param list<string> $order
     */
    public function reorder(Album $album, array $order): void
    {
        $this->assertEditable($album);

        if ($order === []) {
            throw new AlbumException('Ordem de mídias inválida.', 'VALIDATION_ERROR', 400);
        }

        $activeMedia = $this->mediaRepository->findActiveByAlbum($album);
        $byId = [];
        foreach ($activeMedia as $media) {
            if ($media->getMediaType() === AlbumMediaType::Photo) {
                $byId[(string) $media->getId()] = $media;
            }
        }

        if (count($order) !== count($byId)) {
            throw new AlbumException('Lista de ordenação incompleta.', 'VALIDATION_ERROR', 400);
        }

        $sortOrder = 0;
        foreach ($order as $mediaId) {
            if (!isset($byId[$mediaId])) {
                throw new AlbumException('Mídia inválida na ordenação.', 'VALIDATION_ERROR', 400);
            }
            $byId[$mediaId]->setSortOrder($sortOrder++);
        }

        $this->mediaRepository->flush();
    }

    private function assertEditable(Album $album): void
    {
        if (!$album->isEditable()) {
            throw new AlbumException(
                'Upload não permitido para este álbum.',
                'ALBUM_NOT_EDITABLE',
                403,
            );
        }
    }

    private function resolveMimeType(string $mimeType, string $filename, AlbumMediaType $type): string
    {
        $allowed = match ($type) {
            AlbumMediaType::Photo => MediaUploadPolicy::PHOTO_MIMES,
            AlbumMediaType::Audio => MediaUploadPolicy::AUDIO_MIMES,
        };

        if ($mimeType !== '' && in_array($mimeType, $allowed, true)) {
            return $mimeType;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $byExtension = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'mpeg' => 'audio/mpeg',
        ];

        $inferred = $byExtension[$extension] ?? '';
        if ($inferred !== '' && in_array($inferred, $allowed, true)) {
            return $inferred;
        }

        return $mimeType;
    }

    private function assertMimeAndSize(AlbumMediaType $type, string $mimeType, int $sizeBytes): void
    {
        $allowed = match ($type) {
            AlbumMediaType::Photo => MediaUploadPolicy::PHOTO_MIMES,
            AlbumMediaType::Audio => MediaUploadPolicy::AUDIO_MIMES,
        };

        if (!in_array($mimeType, $allowed, true)) {
            throw new AlbumException(
                'Formato não suportado. Use JPEG, PNG ou WebP para fotos; MP3 para áudio.',
                'INVALID_MIME',
                422,
            );
        }

        if ($sizeBytes <= 0) {
            throw new AlbumException('Tamanho do arquivo é obrigatório.', 'VALIDATION_ERROR', 400);
        }

        if ($sizeBytes > $this->maxBytesFor($type)) {
            $maxMb = (int) ceil($this->maxBytesFor($type) / (1024 * 1024));
            throw new AlbumException(
                sprintf('Arquivo excede o tamanho máximo de %d MB.', $maxMb),
                'FILE_TOO_LARGE',
                422,
            );
        }
    }

    private function assertQuota(Album $album, AlbumMediaType $type): void
    {
        if ($type === AlbumMediaType::Photo) {
            $count = $this->mediaRepository->countActiveByAlbumAndType($album, AlbumMediaType::Photo);
            if ($count >= MediaUploadPolicy::MAX_PHOTOS_PER_ALBUM) {
                throw new AlbumException(
                    sprintf('Limite de %d fotos por álbum atingido.', MediaUploadPolicy::MAX_PHOTOS_PER_ALBUM),
                    'PHOTO_QUOTA_EXCEEDED',
                    422,
                );
            }

            return;
        }

        $count = $this->mediaRepository->countActiveByAlbumAndType($album, AlbumMediaType::Audio);
        if ($count >= 1) {
            throw new AlbumException('Apenas uma música de fundo por álbum.', 'AUDIO_QUOTA_EXCEEDED', 422);
        }
    }

    private function maxBytesFor(AlbumMediaType $type): int
    {
        return match ($type) {
            AlbumMediaType::Photo => MediaUploadPolicy::MAX_PHOTO_UPLOAD_BYTES,
            AlbumMediaType::Audio => MediaUploadPolicy::MAX_AUDIO_UPLOAD_BYTES,
        };
    }
}
