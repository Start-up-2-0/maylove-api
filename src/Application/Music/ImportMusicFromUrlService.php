<?php

declare(strict_types=1);

namespace App\Application\Music;

use App\Domain\Shared\UuidValue;
use App\Domain\Tribute\Exception\TributeException;
use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Messenger\Message\ImportMusicFromYoutubeMessage;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\TributeRepository;
use App\Infrastructure\Storage\StorageApiClient;
use Symfony\Component\Messenger\MessageBusInterface;

final class ImportMusicFromUrlService
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
        private readonly TributeMusicStatusService $statusService,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function import(Tribute $tribute, User $user, array $payload): array
    {
        $source = (string) ($payload['source'] ?? '');
        if ($source !== 'youtube') {
            throw new TributeException('Fonte de importação inválida.', 'VALIDATION_ERROR', 400);
        }

        $url = trim((string) ($payload['url'] ?? ''));
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            throw new TributeException('URL inválida.', 'VALIDATION_ERROR', 400);
        }

        if (!preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be)/#i', $url)) {
            throw new TributeException('Informe um link válido do YouTube.', 'VALIDATION_ERROR', 400);
        }

        if (!$tribute->getTemplate()->supportsMusic()) {
            throw new TributeException('Template não suporta música.', 'MUSIC_NOT_SUPPORTED', 422);
        }

        $content = $tribute->getContentJson();
        $content['music_import'] = [
            'status' => 'processing',
            'error' => null,
            'media_id' => null,
        ];
        $tribute->setContentJson($content);
        $this->tributeRepository->save($tribute);

        $this->messageBus->dispatch(new ImportMusicFromYoutubeMessage(
            (string) $tribute->getId(),
            $url,
            (string) $user->getId(),
        ));

        return $this->statusService->resolveForTribute($tribute);
    }
}
