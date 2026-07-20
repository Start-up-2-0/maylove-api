<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Album;

use App\Application\Album\AlbumQrService;
use App\Application\Album\AlbumService;
use App\Domain\Album\Exception\AlbumException;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Infrastructure\Security\MayLoveUser;

#[Route('/albums/{albumId}/qr', name: 'albums_qr_')]
final class AlbumQrController extends AbstractController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly AlbumQrService $qrService,
    ) {
    }

    #[Route('', name: 'view', methods: ['GET'])]
    public function view(string $albumId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            $memoryId = $request->query->get('m');
            $data = $this->qrService->build($album, is_string($memoryId) ? $memoryId : null);

            return ApiResponse::success('OK', $data);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
