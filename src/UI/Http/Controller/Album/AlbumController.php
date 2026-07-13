<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Album;

use App\Application\Album\AlbumPublishService;
use App\Application\Album\AlbumSerializer;
use App\Application\Album\AlbumService;
use App\Application\Album\AlbumValidationService;
use App\Application\Album\BookPresentationCatalogService;
use App\Application\Media\MediaUploadPolicy;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/albums', name: 'albums_')]
final class AlbumController extends AbstractController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly AlbumValidationService $validationService,
        private readonly AlbumPublishService $publishService,
        private readonly AlbumSerializer $serializer,
        private readonly MediaUploadPolicy $uploadPolicy,
        private readonly BookPresentationCatalogService $bookPresentationCatalog,
    ) {
    }

    #[Route('/book-presentations', name: 'book_presentations', methods: ['GET'])]
    public function bookPresentations(): JsonResponse
    {
        return ApiResponse::success('OK', $this->bookPresentationCatalog->list());
    }

    #[Route('/upload-policy', name: 'upload_policy', methods: ['GET'])]
    public function uploadPolicy(#[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success('OK', $this->uploadPolicy->albumPolicy());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->create($user->getUser());

            return ApiResponse::success(
                'Álbum criado com sucesso.',
                $this->serializer->serializeDetail($album),
                Response::HTTP_CREATED,
            );
        } catch (AlbumException $exception) {
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
        $albums = $this->albumService->listByUser(
            $user->getUser(),
            is_string($status) ? $status : null,
        );

        return ApiResponse::success('OK', array_map(
            fn ($album) => $this->serializer->serializeSummary($album),
            $albums,
        ));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($id, $user->getUser());

            return ApiResponse::success('OK', $this->serializer->serializeDetail($album));
        } catch (AlbumException $exception) {
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
            $album = $this->albumService->getOwned($id, $user->getUser());
            $album = $this->albumService->update($album, $payload);

            return ApiResponse::success('Álbum atualizado.', $this->serializer->serializeDetail($album));
        } catch (AlbumException $exception) {
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
            $album = $this->albumService->getOwned($id, $user->getUser());
            $this->albumService->delete($album);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}/validate', name: 'validate', methods: ['POST'])]
    public function validateAlbum(string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($id, $user->getUser());

            return ApiResponse::success('OK', $this->validationService->validate($album));
        } catch (AlbumException $exception) {
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
            $album = $this->albumService->getOwned($id, $user->getUser());
            $album = $this->publishService->publish($album);

            return ApiResponse::success('Álbum publicado.', $this->serializer->serializeDetail($album));
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }
}
