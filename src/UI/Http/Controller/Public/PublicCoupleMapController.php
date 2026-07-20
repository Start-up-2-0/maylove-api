<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Public;

use App\Application\Public\CoupleMapViewService;
use App\Application\Public\PublicCoupleMapQueryService;
use App\Application\Public\PublicCoupleMapSerializer;
use App\Domain\Map\Exception\CoupleMapException;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/maps', name: 'public_maps_')]
final class PublicCoupleMapController extends AbstractController
{
    public function __construct(
        private readonly PublicCoupleMapQueryService $queryService,
        private readonly PublicCoupleMapSerializer $serializer,
        private readonly CoupleMapViewService $viewService,
    ) {
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        try {
            $map = $this->queryService->getPublishedBySlug($slug);

            return ApiResponse::success('OK', $this->serializer->serialize($map));
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{slug}/view', name: 'view', methods: ['POST'])]
    public function recordView(string $slug, Request $request): Response
    {
        /** @var array{session_id?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $this->viewService->recordView($slug, (string) ($payload['session_id'] ?? ''));
        } catch (CoupleMapException) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
