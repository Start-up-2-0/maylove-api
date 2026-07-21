<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Bouquet;

use App\Application\Bouquet\DigitalBouquetCheckoutService;
use App\Application\Bouquet\DigitalBouquetPublishService;
use App\Application\Bouquet\DigitalBouquetSerializer;
use App\Application\Bouquet\DigitalBouquetService;
use App\Application\Bouquet\DigitalBouquetValidationService;
use App\Domain\Bouquet\Exception\DigitalBouquetException;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/bouquets', name: 'bouquets_')]
final class DigitalBouquetController extends AbstractController
{
    public function __construct(
        private readonly DigitalBouquetService $bouquetService,
        private readonly DigitalBouquetValidationService $validationService,
        private readonly DigitalBouquetPublishService $publishService,
        private readonly DigitalBouquetCheckoutService $checkoutService,
        private readonly DigitalBouquetSerializer $serializer,
    ) {
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
            $bouquet = $this->bouquetService->create($user->getUser(), $payload);

            return ApiResponse::success(
                'Buquê criado com sucesso.',
                $this->serializer->serializeDetail($bouquet),
                Response::HTTP_CREATED,
            );
        } catch (DigitalBouquetException $exception) {
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
        $bouquets = $this->bouquetService->listByUser(
            $user->getUser(),
            is_string($status) ? $status : null,
        );

        return ApiResponse::success('OK', array_map(
            fn ($bouquet) => $this->serializer->serializeSummary($bouquet),
            $bouquets,
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $bouquet = $this->bouquetService->getOwned($id, $user->getUser());

            return ApiResponse::success('OK', $this->serializer->serializeDetail($bouquet));
        } catch (DigitalBouquetException $exception) {
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
            $bouquet = $this->bouquetService->getOwned($id, $user->getUser());
            $bouquet = $this->bouquetService->update($bouquet, $payload);

            return ApiResponse::success('OK', $this->serializer->serializeDetail($bouquet));
        } catch (DigitalBouquetException $exception) {
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
            $bouquet = $this->bouquetService->getOwned($id, $user->getUser());
            $this->bouquetService->archive($bouquet);

            return ApiResponse::success('Buquê arquivado.');
        } catch (DigitalBouquetException $exception) {
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
            $bouquet = $this->bouquetService->getOwned($id, $user->getUser());

            return ApiResponse::success('OK', $this->validationService->validate($bouquet));
        } catch (DigitalBouquetException $exception) {
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
            $bouquet = $this->bouquetService->getOwned($id, $user->getUser());
            $bouquet = $this->publishService->publish($bouquet, $user->getUser());

            return ApiResponse::success('Buquê publicado.', [
                'status' => $bouquet->getStatus()->value,
                'public_url' => sprintf('https://maylove.com.br/bouquet/%s', $bouquet->getSlug()),
            ]);
        } catch (DigitalBouquetException $exception) {
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
            $bouquet = $this->bouquetService->getOwned($id, $user->getUser());
            $checkout = $this->checkoutService->checkout($bouquet, $user->getUser());

            return ApiResponse::success('Checkout iniciado.', $checkout);
        } catch (DigitalBouquetException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
