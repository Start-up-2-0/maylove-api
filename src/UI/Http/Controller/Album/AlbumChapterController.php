<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Album;

use App\Application\Album\AlbumChapterSerializer;
use App\Application\Album\AlbumChapterService;
use App\Application\Album\AlbumService;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Entity\Album;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Infrastructure\Security\MayLoveUser;

#[Route('/albums/{albumId}/chapters', name: 'albums_chapters_')]
final class AlbumChapterController extends AbstractController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly AlbumChapterService $chapterService,
        private readonly AlbumChapterSerializer $serializer,
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
            $chapters = $this->chapterService->listByAlbum($album);

            return ApiResponse::success('OK', $this->serializer->serializeList($chapters));
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
            $chapter = $this->chapterService->create($album, (array) $request->toArray());

            return ApiResponse::success('Capítulo criado.', $this->serializer->serialize($chapter), Response::HTTP_CREATED);
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
            $chapter = $this->chapterService->update($album, $id, (array) $request->toArray());

            return ApiResponse::success('Capítulo atualizado.', $this->serializer->serialize($chapter));
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
            $this->chapterService->delete($album, $id);

            return ApiResponse::success('Capítulo excluído.');
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    /**
     * @throws AlbumException
     */
    private function requireEditable(string $albumId, MayLoveUser $user): Album
    {
        $album = $this->albumService->getOwned($albumId, $user->getUser());
        if (!$album->isEditable()) {
            throw new AlbumException('O álbum não pode ser editado neste status.', 'album_not_editable', Response::HTTP_FORBIDDEN);
        }

        return $album;
    }
}
