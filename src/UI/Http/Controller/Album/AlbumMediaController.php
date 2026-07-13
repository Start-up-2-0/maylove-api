<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Album;

use App\Application\Album\AlbumMediaService;
use App\Application\Album\AlbumService;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/albums/{albumId}/media', name: 'album_media_')]
final class AlbumMediaController extends AbstractController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly AlbumMediaService $mediaService,
    ) {
    }

    #[Route('/presign', name: 'presign', methods: ['POST'])]
    public function presign(string $albumId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            $result = $this->mediaService->presign($album, $user->getUser(), $payload);

            return ApiResponse::success('Upload autorizado.', $result, Response::HTTP_CREATED);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/reorder', name: 'reorder', methods: ['PATCH'])]
    public function reorder(string $albumId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array{order?: list<string>} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $order = $payload['order'] ?? [];

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            $this->mediaService->reorder($album, $order);

            return ApiResponse::success('Ordem atualizada.');
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{mediaId}/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(string $albumId, string $mediaId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            $result = $this->mediaService->confirm($album, $mediaId);

            return ApiResponse::success('Mídia confirmada.', $result);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{mediaId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $albumId, string $mediaId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            $this->mediaService->delete($album, $mediaId);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
