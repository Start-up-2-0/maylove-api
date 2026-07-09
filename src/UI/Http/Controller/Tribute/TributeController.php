<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Tribute;

use App\Application\Tribute\TributeArchiveService;
use App\Application\Tribute\TributeCheckoutService;
use App\Application\Tribute\TributePublishService;
use App\Application\Tribute\TributeSerializer;
use App\Application\Tribute\TributeService;
use App\Application\Tribute\TributeValidationService;
use App\Domain\Tribute\Exception\TributeException;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/tributes', name: 'tributes_')]
final class TributeController extends AbstractController
{
    public function __construct(
        private readonly TributeService $tributeService,
        private readonly TributeArchiveService $archiveService,
        private readonly TributeValidationService $validationService,
        private readonly TributePublishService $publishService,
        private readonly TributeCheckoutService $checkoutService,
        private readonly TributeSerializer $serializer,
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
            $tribute = $this->tributeService->create(
                $user->getUser(),
                (string) ($payload['tribute_type_id'] ?? ''),
                (string) ($payload['template_id'] ?? ''),
            );

            $data = $this->serializer->serializeDetail($tribute);

            return ApiResponse::success(
                'Homenagem criada com sucesso.',
                $data,
                Response::HTTP_CREATED,
            );
        } catch (TributeException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        } catch (\Throwable $exception) {
            return ApiResponse::error(
                'Falha ao criar homenagem: '.$exception->getMessage(),
                'TRIBUTE_CREATE_FAILED',
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $status = $request->query->get('status');
        $tributes = $this->tributeService->listByUser(
            $user->getUser(),
            is_string($status) ? $status : null,
        );

        return ApiResponse::success('OK', array_map(
            fn ($tribute) => $this->serializer->serializeSummary($tribute),
            $tributes,
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($id, $user->getUser());

        return ApiResponse::success('OK', $this->serializer->serializeDetail($tribute));
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(string $id, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $tribute = $this->tributeService->getOwned($id, $user->getUser());
        $tribute = $this->tributeService->update($tribute, $payload);

        return ApiResponse::success('Homenagem atualizada.', $this->serializer->serializeDetail($tribute));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($id, $user->getUser());
        $this->archiveService->archive($tribute);

        return ApiResponse::success('Homenagem excluída.');
    }

    #[Route('/{id}/validate', name: 'validate', methods: ['GET'])]
    public function validateTribute(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($id, $user->getUser());

        return ApiResponse::success('OK', $this->validationService->validate($tribute));
    }

    #[Route('/{id}/preview-data', name: 'preview_data', methods: ['GET'])]
    public function previewData(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($id, $user->getUser());
        $data = $this->serializer->serializeDetail($tribute, includeTemplateConfig: true);
        $data['is_preview'] = true;

        return ApiResponse::success('OK', $data);
    }

    #[Route('/{id}/stats', name: 'stats', methods: ['GET'])]
    public function stats(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($id, $user->getUser());

        return ApiResponse::success('OK', $this->serializer->serializeStats($tribute));
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    public function publish(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($id, $user->getUser());
        $tribute = $this->publishService->publish($tribute, $user->getUser());

        return ApiResponse::success('Homenagem publicada.', $this->serializer->serializeDetail($tribute));
    }

    #[Route('/{id}/checkout', name: 'checkout', methods: ['POST'])]
    public function checkout(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $tribute = $this->tributeService->getOwned($id, $user->getUser());
        $result = $this->checkoutService->checkout($tribute, $user->getUser());

        return ApiResponse::success('Checkout iniciado.', $result);
    }
}
