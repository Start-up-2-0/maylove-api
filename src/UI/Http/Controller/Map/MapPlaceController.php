<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Map;

use App\Application\Map\CoupleMapSerializer;
use App\Application\Map\CoupleMapService;
use App\Application\Map\MapPlaceService;
use App\Domain\Map\Exception\CoupleMapException;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/maps/{mapId}/places', name: 'map_places_')]
final class MapPlaceController extends AbstractController
{
    public function __construct(
        private readonly CoupleMapService $mapService,
        private readonly MapPlaceService $placeService,
        private readonly CoupleMapSerializer $serializer,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $mapId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $place = $this->placeService->create($map, $payload);

            return ApiResponse::success(
                'Local criado.',
                $this->serializer->serializePlace($place, includeMedia: true),
                Response::HTTP_CREATED,
            );
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $mapId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $places = $this->placeService->list($map);

            return ApiResponse::success('OK', array_map(
                fn ($place) => $this->serializer->serializePlace($place, includeMedia: true),
                $places,
            ));
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $mapId, string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $place = $this->placeService->get($map, $id);

            return ApiResponse::success('OK', $this->serializer->serializePlace($place, includeMedia: true));
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(string $mapId, string $id, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $place = $this->placeService->update($map, $id, $payload);

            return ApiResponse::success('OK', $this->serializer->serializePlace($place, includeMedia: true));
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $mapId, string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $this->placeService->delete($map, $id);

            return ApiResponse::success('Local removido.');
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(string $mapId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array{order?: list<string>} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $map = $this->mapService->getOwned($mapId, $user->getUser());
            $this->placeService->reorder($map, $payload['order'] ?? []);

            return ApiResponse::success('Ordem atualizada.');
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
