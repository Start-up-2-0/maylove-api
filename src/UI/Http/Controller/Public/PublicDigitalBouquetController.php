<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Public;

use App\Application\Public\DigitalBouquetViewService;
use App\Application\Public\PublicDigitalBouquetQueryService;
use App\Application\Public\PublicDigitalBouquetSerializer;
use App\Domain\Bouquet\Exception\DigitalBouquetException;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/bouquets', name: 'public_bouquets_')]
final class PublicDigitalBouquetController extends AbstractController
{
    public function __construct(
        private readonly PublicDigitalBouquetQueryService $queryService,
        private readonly PublicDigitalBouquetSerializer $serializer,
        private readonly DigitalBouquetViewService $viewService,
    ) {
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        try {
            $bouquet = $this->queryService->getPublishedBySlug($slug);

            return ApiResponse::success('OK', $this->serializer->serialize($bouquet));
        } catch (DigitalBouquetException $exception) {
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
        } catch (DigitalBouquetException) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
