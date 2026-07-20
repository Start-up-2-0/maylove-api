<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Album;

use App\Application\Album\AlbumExperienceSerializer;
use App\Application\Album\AlbumExperienceService;
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

#[Route('/albums/{albumId}/experiences', name: 'albums_experiences_')]
final class AlbumExperienceController extends AbstractController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly AlbumExperienceService $experienceService,
        private readonly AlbumExperienceSerializer $serializer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $albumId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            $experiences = $this->experienceService->listByAlbum($album);

            return ApiResponse::success('OK', $this->serializer->serializeList($experiences));
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $albumId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->requireEditable($albumId, $user);
            $experience = $this->experienceService->create($album, (array) $request->toArray());

            return ApiResponse::success('Experiência criada.', $this->serializer->serialize($experience), Response::HTTP_CREATED);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(string $albumId, string $id, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->requireEditable($albumId, $user);
            $experience = $this->experienceService->update($album, $id, (array) $request->toArray());

            return ApiResponse::success('Experiência atualizada.', $this->serializer->serialize($experience));
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $albumId, string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->requireEditable($albumId, $user);
            $this->experienceService->delete($album, $id);

            return ApiResponse::success('Experiência excluída.');
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    /**
     * @throws AlbumException
     */
    private function requireEditable(string $albumId, MayLoveUser $user): \App\Infrastructure\Persistence\Entity\Album
    {
        $album = $this->albumService->getOwned($albumId, $user->getUser());
        if (!$album->isEditable()) {
            throw new AlbumException('O álbum não pode ser editado neste status.', 'album_not_editable', Response::HTTP_FORBIDDEN);
        }

        return $album;
    }
}
