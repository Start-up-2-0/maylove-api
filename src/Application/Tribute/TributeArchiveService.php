<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Domain\Billing\OrderStatus;
use App\Domain\Tribute\TributeStatus;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Repository\Billing\OrderRepository;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;
use App\Infrastructure\Persistence\Repository\TributeRepository;
use App\Infrastructure\Storage\StorageApiClient;

final class TributeArchiveService
{
    public function __construct(
        private readonly TributeMediaRepository $mediaRepository,
        private readonly TributeRepository $tributeRepository,
        private readonly OrderRepository $orderRepository,
        private readonly StorageApiClient $storageApiClient,
    ) {
    }

    public function archive(Tribute $tribute): void
    {
        if ($tribute->getStatus() === TributeStatus::Archived) {
            return;
        }

        $this->cancelPendingOrder($tribute);

        $deletedFileIds = [];
        foreach ($this->mediaRepository->findActiveByTribute($tribute) as $media) {
            $fileId = $media->getStorageFileId();
            if ($fileId !== '' && !isset($deletedFileIds[$fileId])) {
                $this->deleteStorageFile($fileId);
                $deletedFileIds[$fileId] = true;
            }
            $media->softDelete();
            $this->mediaRepository->save($media);
        }

        foreach (['tribute', 'og'] as $context) {
            $files = $this->storageApiClient->listFiles($context, (string) $tribute->getId());
            foreach ($files as $file) {
                $fileId = is_string($file['file_id'] ?? null) ? $file['file_id'] : '';
                if ($fileId === '' || isset($deletedFileIds[$fileId])) {
                    continue;
                }
                $this->deleteStorageFile($fileId);
                $deletedFileIds[$fileId] = true;
            }
        }

        $tribute->setOgImageUrl(null);
        $tribute->setMusicMediaId(null);
        $tribute->archive();
        $this->tributeRepository->save($tribute);
    }

    private function cancelPendingOrder(Tribute $tribute): void
    {
        $order = $this->orderRepository->findByTribute($tribute);
        if ($order !== null && $order->getStatus() === OrderStatus::Pending) {
            $order->markCancelled();
            $this->orderRepository->save($order);
        }
    }

    private function deleteStorageFile(string $fileId): void
    {
        try {
            $this->storageApiClient->deleteFile($fileId);
        } catch (\Throwable) {
            // Arquivo pode já ter sido removido; segue limpeza local.
        }
    }
}
