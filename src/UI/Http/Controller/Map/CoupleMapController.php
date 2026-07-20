<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Map;

use App\Application\Map\CoupleMapCheckoutService;
use App\Application\Map\CoupleMapPublishService;
use App\Application\Map\CoupleMapSerializer;
use App\Application\Map\CoupleMapService;
use App\Application\Map\CoupleMapValidationService;
use App\Application\Media\MediaUploadPolicy;
use App\Domain\Map\Exception\CoupleMapException;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/maps', name: 'maps_')]
final class CoupleMapController extends AbstractController
{
    public function __construct(
        private readonly CoupleMapService $mapService,
        private readonly CoupleMapValidationService $validationService,
        private readonly CoupleMapPublishService $publishService,
        private readonly CoupleMapCheckoutService $checkoutService,
        private readonly CoupleMapSerializer $serializer,
        private readonly MediaUploadPolicy $uploadPolicy,
    ) {
    }

    #[Route('/upload-policy', name: 'upload_policy', methods: ['GET'])]
    public function uploadPolicy(#[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success('OK', $this->uploadPolicy->mapPolicy());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $map = $this->mapService->create($user->getUser(), $payload);

            return ApiResponse::success(
                'Mapa criado com sucesso.',
                $this->serializer->serializeDetail($map),
                Response::HTTP_CREATED,
            );
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $status = $request->query->get('status');
        $maps = $this->mapService->listByUser(
            $user->getUser(),
            is_string($status) ? $status : null,
        );

        return ApiResponse::success('OK', array_map(
            fn ($map) => $this->serializer->serializeSummary($map),
            $maps,
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($id, $user->getUser());

            return ApiResponse::success('OK', $this->serializer->serializeDetail($map));
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(string $id, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $map = $this->mapService->getOwned($id, $user->getUser());
            $map = $this->mapService->update($map, $payload);

            return ApiResponse::success('OK', $this->serializer->serializeDetail($map));
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($id, $user->getUser());
            $this->mapService->archive($map);

            return ApiResponse::success('Mapa arquivado.');
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}/validate', name: 'validate', methods: ['GET'])]
    public function validate(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($id, $user->getUser());

            return ApiResponse::success('OK', $this->validationService->validate($map));
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    public function publish(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($id, $user->getUser());
            $map = $this->publishService->publish($map, $user->getUser());

            return ApiResponse::success('Mapa publicado.', [
                'status' => $map->getStatus()->value,
                'public_url' => sprintf('https://maylove.com.br/map/%s', $map->getSlug()),
            ]);
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}/checkout', name: 'checkout', methods: ['POST'])]
    public function checkout(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $map = $this->mapService->getOwned($id, $user->getUser());
            $checkout = $this->checkoutService->checkout($map, $user->getUser());

            return ApiResponse::success('Checkout iniciado.', $checkout);
        } catch (CoupleMapException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
