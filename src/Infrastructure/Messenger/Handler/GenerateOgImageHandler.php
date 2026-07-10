<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Domain\Shared\UuidValue;
use App\Domain\Tribute\TributeMediaType;
use App\Domain\Tribute\TributeStatus;
use App\Infrastructure\Messenger\Message\GenerateOgImageMessage;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;
use App\Infrastructure\Persistence\Repository\TributeRepository;
use App\Infrastructure\Storage\StorageApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GenerateOgImageHandler
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
        private readonly TributeMediaRepository $mediaRepository,
        private readonly StorageApiClient $storageApiClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(GenerateOgImageMessage $message): void
    {
        if (!UuidValue::isValid($message->tributeId)) {
            return;
        }

        $tribute = $this->tributeRepository->find(UuidValue::fromString($message->tributeId));
        if ($tribute === null || $tribute->getStatus() !== TributeStatus::Published) {
            return;
        }

        $sourceFileId = null;
        foreach ($this->mediaRepository->findActiveByTribute($tribute) as $media) {
            if ($media->getMediaType() === TributeMediaType::Photo && $media->getMimeType() !== null) {
                $sourceFileId = $media->getStorageFileId();
                break;
            }
        }

        try {
            $result = $this->storageApiClient->generateOgImage(
                $message->tributeId,
                $sourceFileId,
                $tribute->getTitle() ?? $tribute->getHonoreeName() ?? 'MayLov',
                $tribute->getColorPrimary() ?? $tribute->getTemplate()->getPrimaryColor(),
            );

            $publicUrl = isset($result['public_url']) ? (string) $result['public_url'] : '';
            if ($publicUrl !== '') {
                $tribute->setOgImageUrl($publicUrl);
                $this->tributeRepository->save($tribute);
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Falha ao gerar OG image.', [
                'tribute_id' => $message->tributeId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
