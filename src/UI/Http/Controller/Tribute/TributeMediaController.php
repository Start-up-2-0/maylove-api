<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Tribute;

use App\Application\Music\TributeMusicStatusService;
use App\Application\Tribute\TributeMediaService;
use App\Application\Tribute\TributeService;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/tributes/{tributeId}/media', name: 'tribute_media_')]
final class TributeMediaController extends AbstractController
{
    public function __construct(
        private readonly TributeService $tributeService,
        private readonly TributeMediaService $mediaService,
        private readonly TributeMusicStatusService $musicStatusService,
    ) {
    }

    #[Route('/presign', name: 'presign', methods: ['POST'])]
    public function presign(string $tributeId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $tribute = $this->tributeService->getOwned($tributeId, $user->getUser());
        $result = $this->mediaService->presign($tribute, $user->getUser(), $payload);

        return ApiResponse::success('Upload autorizado.', $result, Response::HTTP_CREATED);
    }

    #[Route('/reorder', name: 'reorder', methods: ['PATCH'])]
    public function reorder(string $tributeId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array{order?: list<string>} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $order = $payload['order'] ?? [];

        $tribute = $this->tributeService->getOwned($tributeId, $user->getUser());
        $this->mediaService->reorder($tribute, $order);

        return ApiResponse::success('Ordem atualizada.');
    }

    #[Route('/{mediaId}/confirm', name: 'confirm', methods: ['POST'])]
    public function confirm(string $tributeId, string $mediaId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($tributeId, $user->getUser());
        $result = $this->mediaService->confirm($tribute, $mediaId);

        return ApiResponse::success('Mídia confirmada.', $result);
    }

    #[Route('/{mediaId}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $tributeId, string $mediaId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($tributeId, $user->getUser());
        $this->mediaService->delete($tribute, $mediaId);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{mediaId}/status', name: 'status', methods: ['GET'])]
    public function status(string $tributeId, string $mediaId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($tributeId, $user->getUser());

        return ApiResponse::success('OK', $this->musicStatusService->resolveForMedia($tribute, $mediaId));
    }
}
