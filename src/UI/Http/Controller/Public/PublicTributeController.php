<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Public;

use App\Application\Public\PublicTributeQueryService;
use App\Application\Public\PublicTributeSerializer;
use App\Application\Public\TributeViewService;
use App\Application\Public\TributeShareService;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/tributes', name: 'public_tributes_')]
final class PublicTributeController extends AbstractController
{
    public function __construct(
        private readonly PublicTributeQueryService $queryService,
        private readonly PublicTributeSerializer $serializer,
        private readonly TributeViewService $viewService,
        private readonly TributeShareService $shareService,
    ) {
    }

    #[Route('/{slug}/share', name: 'share', methods: ['POST'])]
    public function recordShare(string $slug, Request $request): Response
    {
        /** @var array{session_id?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $this->shareService->record($slug, (string) ($payload['session_id'] ?? ''));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $tribute = $this->queryService->getPublishedBySlug($slug);

        return ApiResponse::success('OK', $this->serializer->serialize($tribute));
    }

    #[Route('/{slug}/view', name: 'view', methods: ['POST'])]
    public function recordView(string $slug, Request $request): Response
    {
        /** @var array{session_id?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $securityUser = $this->getUser();
        $viewerUserId = $securityUser instanceof MayLoveUser
            ? $securityUser->getUserIdentifier()
            : null;

        $this->viewService->recordView(
            $slug,
            (string) ($payload['session_id'] ?? ''),
            $viewerUserId,
        );

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
