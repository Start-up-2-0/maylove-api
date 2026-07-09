<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Domain\Shared\UuidValue;
use App\Domain\Tribute\MediaProcessingStatus;
use App\Domain\Tribute\MusicSource;
use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Messenger\Message\ImportMusicFromYoutubeMessage;
use App\Infrastructure\Persistence\Entity\TributeMedia;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;
use App\Infrastructure\Persistence\Repository\TributeRepository;
use App\Infrastructure\Storage\StorageApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ImportMusicFromYoutubeHandler
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
        private readonly TributeMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ImportMusicFromYoutubeMessage $message): void
    {
        if (!UuidValue::isValid($message->tributeId)) {
            return;
        }

        $tribute = $this->tributeRepository->find(UuidValue::fromString($message->tributeId));
        if ($tribute === null) {
            return;
        }

        $content = $tribute->getContentJson();
        $content['music_import'] = [
            'status' => 'processing',
            'error' => null,
            'media_id' => null,
        ];
        $tribute->setContentJson($content);
        $this->tributeRepository->save($tribute);

        try {
            $result = $this->storageApiClient->importYoutubeAudio(
                (string) $tribute->getId(),
                $message->url,
                $message->userId,
            );

            foreach ($this->mediaRepository->findActiveByTribute($tribute) as $existing) {
                if ($existing->getMediaType() === TributeMediaType::Audio) {
                    $existing->softDelete();
                }
            }

            $media = new TributeMedia(
                $tribute,
                (string) ($result['file_id'] ?? ''),
                TributeMediaType::Audio,
                (string) ($result['original_filename'] ?? 'youtube-audio.mp3'),
                $this->mediaRepository->getNextSortOrder($tribute),
            );
            $media->setMimeType((string) ($result['mime_type'] ?? 'audio/mpeg'));
            $media->setSizeBytes(isset($result['size_bytes']) ? (int) $result['size_bytes'] : null);
            $media->setProcessingStatus(MediaProcessingStatus::Ready);

            $tribute->setMusicSource(MusicSource::Upload);
            $tribute->setMusicMediaId($media->getId());
            $tribute->setMusicTrackId(null);

            $content = $tribute->getContentJson();
            if (isset($result['duration_seconds'])) {
                $content['music_duration_seconds'] = (float) $result['duration_seconds'];
                $content['music_start_seconds'] = 0.0;
                $content['music_end_seconds'] = (float) $result['duration_seconds'];
            }
            $content['music_import'] = [
                'status' => 'ready',
                'error' => null,
                'media_id' => (string) $media->getId(),
            ];
            $tribute->setContentJson($content);

            $this->mediaRepository->save($media);
            $this->tributeRepository->save($tribute);
        } catch (\Throwable $exception) {
            $errorMessage = $this->resolveImportErrorMessage($exception);
            $content = $tribute->getContentJson();
            $content['music_import'] = [
                'status' => 'failed',
                'error' => $errorMessage,
                'media_id' => null,
            ];
            $tribute->setContentJson($content);
            $this->tributeRepository->save($tribute);

            $this->logger->error('Falha ao importar áudio do YouTube.', [
                'tribute_id' => $message->tributeId,
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
