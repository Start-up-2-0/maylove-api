<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Messenger\Message\ImportAlbumMusicFromYoutubeMessage;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\AlbumRepository;
use Symfony\Component\Messenger\MessageBusInterface;

final class ImportAlbumMusicFromUrlService
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumMusicStatusService $statusService,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function import(Album $album, User $user, array $payload): array
    {
        if (!$album->isEditable()) {
            throw new AlbumException('Álbum não pode ser editado.', 'ALBUM_NOT_EDITABLE', 403);
        }

        $source = (string) ($payload['source'] ?? '');
        if ($source !== 'youtube') {
            throw new AlbumException('Fonte de importação inválida.', 'VALIDATION_ERROR', 400);
        }

        $url = trim((string) ($payload['url'] ?? ''));
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            throw new AlbumException('URL inválida.', 'VALIDATION_ERROR', 400);
        }

        if (!preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be)/#i', $url)) {
            throw new AlbumException('Informe um link válido do YouTube.', 'VALIDATION_ERROR', 400);
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

        $this->messageBus->dispatch(new ImportAlbumMusicFromYoutubeMessage(
            (string) $album->getId(),
            $url,
            (string) $user->getId(),
        ));

        return $this->statusService->resolveForAlbum($album);
    }
}
