<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Map;

use App\Application\Map\CoupleMapService;
use App\Application\Map\MapMediaService;
use App\Domain\Map\Exception\CoupleMapException;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/maps/{mapId}/media', name: 'map_media_')]
final class MapMediaController extends AbstractController
{
    public function __construct(
        private readonly CoupleMapService $mapService,
        private readonly MapMediaService $mediaService,
    ) {
    }

    #[Route('/presign', name: 'presign', methods: ['POST'])]
    public function presign(string $mapId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $result = $this->mediaService->presign($map, $user->getUser(), $payload);

            return ApiResponse::success('OK', $result);
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{mediaId}/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(string $mapId, string $mediaId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $media = $this->mediaService->confirm($map, $mediaId);

            return ApiResponse::success('Mídia confirmada.', $media);
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{mediaId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $mapId, string $mediaId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $this->mediaService->delete($map, $mediaId);

            return ApiResponse::success('Mídia removida.');
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
