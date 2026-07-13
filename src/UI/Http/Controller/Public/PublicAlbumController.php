<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Public;

use App\Application\Public\AlbumViewService;
use App\Application\Public\PublicAlbumQueryService;
use App\Application\Public\PublicAlbumSerializer;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/albums', name: 'public_albums_')]
final class PublicAlbumController extends AbstractController
{
    public function __construct(
        private readonly PublicAlbumQueryService $queryService,
        private readonly PublicAlbumSerializer $serializer,
        private readonly AlbumViewService $viewService,
    ) {
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $album = $this->queryService->getPublishedBySlug($slug);

        return ApiResponse::success('OK', $this->serializer->serialize($album));
    }

    #[Route('/{slug}/view', name: 'view', methods: ['POST'])]
    public function recordView(string $slug, Request $request): Response
    {
        /** @var array{session_id?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $this->viewService->recordView($slug, (string) ($payload['session_id'] ?? ''));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
