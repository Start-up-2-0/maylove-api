<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Tribute;

use App\Application\Music\ImportMusicFromUrlService;
use App\Application\Music\TributeMusicStatusService;
use App\Application\Tribute\TributeService;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/tributes/{tributeId}/music', name: 'tribute_music_')]
final class TributeMusicController extends AbstractController
{
    public function __construct(
        private readonly TributeService $tributeService,
        private readonly ImportMusicFromUrlService $importMusicFromUrlService,
        private readonly TributeMusicStatusService $statusService,
    ) {
    }

    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(string $tributeId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $tribute = $this->tributeService->getOwned($tributeId, $user->getUser());
        $result = $this->importMusicFromUrlService->import($tribute, $user->getUser(), $payload);

        return ApiResponse::success('Importação iniciada.', $result, Response::HTTP_ACCEPTED);
    }

    #[Route('/status', name: 'status', methods: ['GET'])]
    public function status(string $tributeId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($tributeId, $user->getUser());

        return ApiResponse::success('OK', $this->statusService->resolveForTribute($tribute));
    }
}
