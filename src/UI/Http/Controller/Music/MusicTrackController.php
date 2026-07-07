<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Music;

use App\Application\Music\MusicTrackQueryService;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/music-tracks', name: 'music_tracks_')]
final class MusicTrackController extends AbstractController
{
    public function __construct(
        private readonly MusicTrackQueryService $musicTrackQueryService,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $category = $request->query->get('category');
        $result = $this->musicTrackQueryService->list(is_string($category) ? $category : null);

        return ApiResponse::success('OK', $result);
    }
}
