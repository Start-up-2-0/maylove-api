<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Application\Album\AlbumMusicConfig;
use App\Domain\Album\AlbumMediaType;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Messenger\Message\ImportAlbumMusicFromYoutubeMessage;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
use App\Infrastructure\Persistence\Repository\AlbumRepository;
use App\Infrastructure\Storage\StorageApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ImportAlbumMusicFromYoutubeHandler
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ImportAlbumMusicFromYoutubeMessage $message): void
    {
        if (!UuidValue::isValid($message->albumId)) {
            return;
        }

        $album = $this->albumRepository->find(UuidValue::fromString($message->albumId));
        if ($album === null) {
            return;
        }

        AlbumMusicConfig::merge($album, [
            'source' => 'upload',
            'import' => [
                'status' => 'processing',
                'error' => null,
                'media_id' => null,
            ],
        ]);
        $this->albumRepository->save($album);

        try {
            $result = $this->storageApiClient->importYoutubeAudio(
                (string) $album->getId(),
                $message->url,
                $message->userId,
                'album',
            );

            foreach ($this->mediaRepository->findActiveByAlbum($album) as $existing) {
                if ($existing->getMediaType() === AlbumMediaType::Audio) {
                    $existing->softDelete();
                    $this->mediaRepository->save($existing);
                }
            }

            $media = new AlbumMedia(
                $album,
                (string) ($result['file_id'] ?? ''),
                AlbumMediaType::Audio,
                (string) ($result['original_filename'] ?? 'youtube-audio.mp3'),
                $this->mediaRepository->getNextSortOrder($album),
            );
            $media->setMimeType((string) ($result['mime_type'] ?? 'audio/mpeg'));
            $media->setSizeBytes(isset($result['size_bytes']) ? (int) $result['size_bytes'] : null);

            $album->setMusicMediaId($media->getId());

            $duration = isset($result['duration_seconds']) ? (float) $result['duration_seconds'] : 0.0;
            AlbumMusicConfig::merge($album, [
                'source' => 'upload',
                'duration_seconds' => $duration,
                'start_seconds' => 0.0,
                'end_seconds' => $duration,
                'import' => [
                    'status' => 'ready',
                    'error' => null,
                    'media_id' => (string) $media->getId(),
                ],
            ]);

            $this->mediaRepository->save($media);
            $this->albumRepository->save($album);
        } catch (\Throwable $exception) {
            $errorMessage = $this->resolveImportErrorMessage($exception);
            AlbumMusicConfig::merge($album, [
                'import' => [
                    'status' => 'failed',
                    'error' => $errorMessage,
                    'media_id' => null,
                ],
            ]);
            $this->albumRepository->save($album);

            $this->logger->error('Falha ao importar áudio do YouTube para álbum.', [
                'album_id' => $message->albumId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveImportErrorMessage(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        if ($message === '') {
            return 'Não foi possível importar o áudio do YouTube.';
        }

        if (str_contains($message, 'Não foi possível') || str_contains($message, 'indisponível') || str_contains($message, 'bloqueou')) {
            return $message;
        }

        return 'Não foi possível importar o áudio do YouTube.';
    }
}
