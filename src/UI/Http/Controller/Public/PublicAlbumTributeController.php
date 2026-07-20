<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Public;

use App\Application\Public\AlbumVisitorTributeService;
use App\Application\Public\PublicAlbumQueryService;
use App\Domain\Album\Exception\AlbumException;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/albums/{slug}/tributes', name: 'public_albums_tributes_')]
final class PublicAlbumTributeController extends AbstractController
{
    public function __construct(
        private readonly PublicAlbumQueryService $queryService,
        private readonly AlbumVisitorTributeService $tributeService,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $slug): JsonResponse
    {
        try {
            $album = $this->queryService->getPublishedBySlug($slug);

            return ApiResponse::success('OK', $this->tributeService->listActive($album));
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $slug, Request $request): JsonResponse
    {
        try {
            $album = $this->queryService->getPublishedBySlug($slug);
            /** @var array<string, mixed> $payload */
            $payload = json_decode($request->getContent(), true) ?? [];
            $tribute = $this->tributeService->create($album, $payload, $request->getClientIp());

            return ApiResponse::success('Homenagem registrada.', $tribute, Response::HTTP_CREATED);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
