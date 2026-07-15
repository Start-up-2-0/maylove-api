<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Album;

use App\Application\Album\AlbumMusicStatusService;
use App\Application\Album\AlbumService;
use App\Application\Album\ImportAlbumMusicFromUrlService;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/albums/{albumId}/music', name: 'album_music_')]
final class AlbumMusicController extends AbstractController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly ImportAlbumMusicFromUrlService $importMusicFromUrlService,
        private readonly AlbumMusicStatusService $statusService,
    ) {
    }

    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(string $albumId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            $result = $this->importMusicFromUrlService->import($album, $user->getUser(), $payload);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }

        return ApiResponse::success('Importação iniciada.', $result, Response::HTTP_ACCEPTED);
    }

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(string $albumId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());

            return ApiResponse::success('OK', $this->statusService->resolveForAlbum($album));
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
