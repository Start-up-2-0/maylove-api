<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Album;

use App\Application\Album\AlbumMemorySerializer;
use App\Application\Album\AlbumMemoryService;
use App\Application\Album\AlbumService;
use App\Domain\Album\Exception\AlbumException;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumChapter;
use App\Infrastructure\Persistence\Repository\AlbumChapterRepository;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Infrastructure\Security\MayLoveUser;

#[Route('/albums/{albumId}/chapters/{chapterId}/memories', name: 'albums_memories_')]
final class AlbumMemoryController extends AbstractController
{
    public function __construct(
        private readonly AlbumService $albumService,
        private readonly AlbumChapterRepository $chapterRepository,
        private readonly AlbumMemoryService $memoryService,
        private readonly AlbumMemorySerializer $serializer,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $albumId, string $chapterId, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $chapter = $this->resolveChapter($albumId, $chapterId, $user);
            $memories = $this->memoryService->listByChapter($chapter);

            return ApiResponse::success('OK', $this->serializer->serializeList($memories));
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $albumId, string $chapterId, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $chapter = $this->resolveEditable($albumId, $chapterId, $user);
            $album = $chapter->getAlbum();
            $memory = $this->memoryService->create($chapter, $album, (array) $request->toArray());

            return ApiResponse::success('Memória criada.', $this->serializer->serialize($memory), Response::HTTP_CREATED);
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(string $albumId, string $chapterId, string $id, Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            if (!$album->isEditable()) {
                throw new AlbumException('O álbum não pode ser editado neste status.', 'album_not_editable', Response::HTTP_FORBIDDEN);
            }
            $memory = $this->memoryService->update($album, $id, (array) $request->toArray());

            return ApiResponse::success('Memória atualizada.', $this->serializer->serialize($memory));
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $albumId, string $chapterId, string $id, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $album = $this->albumService->getOwned($albumId, $user->getUser());
            if (!$album->isEditable()) {
                throw new AlbumException('O álbum não pode ser editado neste status.', 'album_not_editable', Response::HTTP_FORBIDDEN);
            }
            $this->memoryService->delete($album, $id);

            return ApiResponse::success('Memória excluída.');
        } catch (AlbumException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }
    }

    /**
     * @throws AlbumException
     */
    private function resolveChapter(string $albumId, string $chapterId, MayLoveUser $user): AlbumChapter
    {
        $album = $this->albumService->getOwned($albumId, $user->getUser());
        $chapter = $this->chapterRepository->findByIdAndAlbum(\Symfony\Component\Uid\Uuid::fromString($chapterId), $album);
        if ($chapter === null) {
            throw new AlbumException('Capítulo não encontrado.', 'chapter_not_found', 404);
        }

        return $chapter;
    }

    /**
     * @throws AlbumException
     */
    private function resolveEditable(string $albumId, string $chapterId, MayLoveUser $user): AlbumChapter
    {
        $album = $this->albumService->getOwned($albumId, $user->getUser());
        if (!$album->isEditable()) {
            throw new AlbumException('O álbum não pode ser editado neste status.', 'album_not_editable', Response::HTTP_FORBIDDEN);
        }

        return $this->resolveChapter($albumId, $chapterId, $user);
    }
}
