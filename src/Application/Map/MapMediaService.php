<?php

declare(strict_types=1);

namespace App\Application\Map;

use App\Application\Media\MediaUploadPolicy;
use App\Application\Tribute\MediaPresignRateLimitService;
use App\Domain\Map\Exception\CoupleMapException;
use App\Domain\Map\MapMediaType;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\MapPlace;
use App\Infrastructure\Persistence\Entity\MapPlaceMedia;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\MapPlaceMediaRepository;
use App\Infrastructure\Persistence\Repository\MapPlaceRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class MapMediaService
{
    private const VIDEO_MIMES = ['video/mp4'];
    private const MAX_MEDIA_PER_PLACE = 10;
    private const MAX_VIDEO_BYTES = 100 * 1024 * 1024;

    public function __construct(
        private readonly MapPlaceMediaRepository $mediaRepository,
        private readonly MapPlaceRepository $placeRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly MediaPresignRateLimitService $rateLimitService,
        private readonly CoupleMapSerializer $serializer,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function presign(CoupleMap $map, User $user, array $payload): array
    {
        $this->assertEditable($map);

        $mapId = (string) $map->getId();
        if (!$this->rateLimitService->canPresign($mapId)) {
            throw new CoupleMapException('Limite de uploads atingido. Tente novamente em breve.', 'RATE_LIMIT', 429);
        }

        $mediaType = MapMediaType::tryFrom((string) ($payload['media_type'] ?? ''));
        if ($mediaType === null) {
            throw new CoupleMapException('Tipo de mídia inválido.', 'VALIDATION_ERROR', 400);
        }

        $placeId = (string) ($payload['place_id'] ?? '');
        if ($placeId === '') {
            throw new CoupleMapException('Local é obrigatório para upload.', 'VALIDATION_ERROR', 422);
        }

        $place = $this->requirePlace($map, $placeId);

        if ($this->mediaRepository->countActiveByPlace($place) >= self::MAX_MEDIA_PER_PLACE) {
            throw new CoupleMapException(
                sprintf('Limite de %d mídias por local atingido.', self::MAX_MEDIA_PER_PLACE),
                'MEDIA_QUOTA_EXCEEDED',
                422,
            );
        }

        $filename = basename(str_replace('\\', '/', (string) ($payload['filename'] ?? '')));
        $mimeType = strtolower(trim((string) ($payload['mime_type'] ?? '')));
        $sizeBytes = (int) ($payload['size_bytes'] ?? 0);

        if ($filename === '' || str_contains($filename, '..')) {
            throw new CoupleMapException('Nome de arquivo inválido.', 'INVALID_FILENAME', 400);
        }

        $mimeType = $this->resolveMimeType($mimeType, $filename, $mediaType);
        $this->assertMimeAndSize($mediaType, $mimeType, $sizeBytes);

        $ticket = $this->storageApiClient->createUploadTicket([
            'context' => 'map',
            'context_id' => $mapId,
            'media_type' => $mediaType->value,
            'original_filename' => $filename,
            'mime_type' => $mimeType,
            'max_size_bytes' => $this->maxBytesFor($mediaType),
            'ttl_seconds' => 900,
            'user_id' => (string) $user->getId(),
        ]);

        $media = new MapPlaceMedia(
            $map,
            $place,
            (string) ($ticket['file_id'] ?? ''),
            $mediaType,
            $filename,
            $this->mediaRepository->getNextSortOrder($place),
        );
        $media->setMimeType($mimeType);
        $media->setSizeBytes($sizeBytes > 0 ? $sizeBytes : null);

        $this->mediaRepository->save($media);
        $this->rateLimitService->registerPresign($mapId);

        return [
            'media_id' => (string) $media->getId(),
            'storage_file_id' => (string) ($ticket['file_id'] ?? ''),
            'upload_url' => (string) ($ticket['upload_url'] ?? ''),
            'upload_ticket' => (string) ($ticket['upload_ticket'] ?? ''),
            'expires_at' => (string) ($ticket['expires_at'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    public function confirm(CoupleMap $map, string $mediaId): array
    {
        $this->assertEditable($map);

        $media = $this->requireMedia($map, $mediaId);

        $confirmed = $this->storageApiClient->confirmFile(
            $media->getStorageFileId(),
            $media->getMediaType()->value,
            (string) ($media->getMimeType() ?? 'application/octet-stream'),
        );

        $media->setMimeType(isset($confirmed['mime_type']) ? (string) $confirmed['mime_type'] : $media->getMimeType());
        $media->setSizeBytes(isset($confirmed['size_bytes']) ? (int) $confirmed['size_bytes'] : $media->getSizeBytes());
        $thumbnail = $confirmed['thumbnail_url'] ?? null;
        $media->setUrlThumbnail(is_string($thumbnail) ? $thumbnail : null);

        $this->mediaRepository->save($media);

        return $this->serializer->serializeMedia($media, includeUrl: true);
    }

    public function delete(CoupleMap $map, string $mediaId): void
    {
        $this->assertEditable($map);
        $media = $this->requireMedia($map, $mediaId);

        try {
            $this->storageApiClient->deleteFile($media->getStorageFileId());
        } catch (\Throwable) {
        }

        $media->softDelete();
        $this->mediaRepository->save($media);
    }

    private function resolveMimeType(string $mimeType, string $filename, MapMediaType $type): string
    {
        $allowed = match ($type) {
            MapMediaType::Photo => MediaUploadPolicy::PHOTO_MIMES,
            MapMediaType::Video => self::VIDEO_MIMES,
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
            'mp4' => 'video/mp4',
        ];

        $inferred = $byExtension[$extension] ?? '';

        return $inferred !== '' && in_array($inferred, $allowed, true) ? $inferred : $mimeType;
    }

    private function assertMimeAndSize(MapMediaType $type, string $mimeType, int $sizeBytes): void
    {
        $allowed = match ($type) {
            MapMediaType::Photo => MediaUploadPolicy::PHOTO_MIMES,
            MapMediaType::Video => self::VIDEO_MIMES,
        };

        if (!in_array($mimeType, $allowed, true)) {
            throw new CoupleMapException(
                'Formato não suportado. Use JPEG, PNG ou WebP para fotos; MP4 para vídeo.',
                'INVALID_MIME',
                422,
            );
        }

        if ($sizeBytes <= 0) {
            throw new CoupleMapException('Tamanho do arquivo é obrigatório.', 'VALIDATION_ERROR', 400);
        }

        if ($sizeBytes > $this->maxBytesFor($type)) {
            $maxMb = (int) ceil($this->maxBytesFor($type) / (1024 * 1024));
            throw new CoupleMapException(
                sprintf('Arquivo excede o tamanho máximo de %d MB.', $maxMb),
                'FILE_TOO_LARGE',
                422,
            );
        }
    }

    private function maxBytesFor(MapMediaType $type): int
    {
        return match ($type) {
            MapMediaType::Photo => MediaUploadPolicy::MAX_PHOTO_UPLOAD_BYTES,
            MapMediaType::Video => self::MAX_VIDEO_BYTES,
        };
    }

    private function requirePlace(CoupleMap $map, string $placeId): MapPlace
    {
        if (!UuidValue::isValid($placeId)) {
            throw new CoupleMapException('Local não encontrado.', 'PLACE_NOT_FOUND', 404);
        }

        $place = $this->placeRepository->findActiveByIdAndMap(UuidValue::fromString($placeId), $map);
        if ($place === null) {
            throw new CoupleMapException('Local não encontrado.', 'PLACE_NOT_FOUND', 404);
        }

        return $place;
    }

    private function requireMedia(CoupleMap $map, string $mediaId): MapPlaceMedia
    {
        if (!UuidValue::isValid($mediaId)) {
            throw new CoupleMapException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        $media = $this->mediaRepository->findActiveByIdAndMap(UuidValue::fromString($mediaId), $map);
        if ($media === null) {
            throw new CoupleMapException('Mídia não encontrada.', 'MEDIA_NOT_FOUND', 404);
        }

        return $media;
    }

    private function assertEditable(CoupleMap $map): void
    {
        if (!$map->isEditable()) {
            throw new CoupleMapException('Upload não permitido para este mapa.', 'MAP_NOT_EDITABLE', 403);
        }
    }
}
