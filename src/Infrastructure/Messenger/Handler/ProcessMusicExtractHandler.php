<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Domain\Shared\UuidValue;
use App\Domain\Tribute\MediaProcessingStatus;
use App\Domain\Tribute\MusicSource;
use App\Infrastructure\Messenger\Message\ProcessMusicExtractMessage;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;
use App\Infrastructure\Persistence\Repository\TributeRepository;
use App\Infrastructure\Storage\StorageApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProcessMusicExtractHandler
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
        private readonly TributeMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProcessMusicExtractMessage $message): void
    {
        if (!UuidValue::isValid($message->tributeId) || !UuidValue::isValid($message->mediaId)) {
            return;
        }

        $tribute = $this->tributeRepository->find(UuidValue::fromString($message->tributeId));
        if ($tribute === null) {
            return;
        }

        $media = $this->mediaRepository->findActiveByIdAndTribute(
            UuidValue::fromString($message->mediaId),
            $tribute,
        );

        if ($media === null) {
            return;
        }

        try {
            $result = $this->storageApiClient->extractAudio($media->getStorageFileId());
            $media->setMimeType((string) ($result['mime_type'] ?? 'audio/mpeg'));
            $media->setSizeBytes(isset($result['size_bytes']) ? (int) $result['size_bytes'] : $media->getSizeBytes());
            $media->setProcessingStatus(MediaProcessingStatus::Ready);
            $media->setProcessingError(null);

            $content = $tribute->getContentJson();
            if (isset($result['duration_seconds'])) {
                $content['music_duration_seconds'] = (float) $result['duration_seconds'];
                $content['music_start_seconds'] = 0.0;
                $content['music_end_seconds'] = (float) $result['duration_seconds'];
            }
            $tribute->setContentJson($content);
            $tribute->setMusicSource(MusicSource::Upload);
            $tribute->setMusicMediaId($media->getId());
            $tribute->setMusicTrackId(null);

            $this->mediaRepository->save($media);
            $this->tributeRepository->save($tribute);
        } catch (\Throwable $exception) {
            $media->setProcessingStatus(MediaProcessingStatus::Failed);
            $media->setProcessingError('Não foi possível extrair o áudio do vídeo.');
            $this->mediaRepository->save($media);

            $this->logger->error('Falha ao extrair áudio.', [
                'tribute_id' => $message->tributeId,
                'media_id' => $message->mediaId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
