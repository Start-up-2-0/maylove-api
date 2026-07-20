<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Domain\Album\AlbumCategory;
use App\Domain\Album\AlbumMediaType;
use App\Domain\Album\BookPresentation;
use App\Domain\Album\Exception\AlbumException;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
use App\Infrastructure\Persistence\Repository\AlbumRepository;
use App\Infrastructure\Persistence\Repository\UserRepository;

final class AlbumService
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumMediaRepository $mediaRepository,
        private readonly AlbumSlugGenerator $slugGenerator,
        private readonly UserRepository $userRepository,
        private readonly bool $emailVerificationEnabled,
    ) {
    }

    public function create(User $user): Album
    {
        $managedUser = $this->userRepository->find($user->getId());
        if ($managedUser === null) {
            throw new AlbumException('Usuário não encontrado.', 'USER_NOT_FOUND', 404);
        }

        if ($this->emailVerificationEnabled && !$managedUser->isEmailVerified()) {
            throw new AlbumException(
                'Confirme seu e-mail antes de criar um álbum.',
                'EMAIL_NOT_VERIFIED',
                403,
            );
        }

        $album = new Album($managedUser, $this->slugGenerator->generateUnique());
        $album->setTitle('Meu álbum de memórias');
        $this->albumRepository->save($album);

        return $album;
    }

    /**
     * @return list<Album>
     */
    public function listByUser(User $user, ?string $status = null): array
    {
        $albumStatus = null;
        if ($status !== null && $status !== '') {
            $albumStatus = \App\Domain\Album\AlbumStatus::tryFrom($status);
        }

        return $this->albumRepository->findByUser($user, $albumStatus);
    }

    public function getOwned(string $id, User $user): Album
    {
        if (!UuidValue::isValid($id)) {
            throw new AlbumException('Álbum não encontrado.', 'ALBUM_NOT_FOUND', 404);
        }

        $album = $this->albumRepository->findOwnedById(UuidValue::fromString($id), $user->getId());
        if ($album === null) {
            throw new AlbumException('Álbum não encontrado.', 'ALBUM_NOT_FOUND', 404);
        }

        return $album;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{album: Album, music_cleared: bool}
     */
    public function update(Album $album, array $payload): array
    {
        if (!$album->isEditable()) {
            throw new AlbumException('Álbum não pode ser editado.', 'ALBUM_NOT_EDITABLE', 403);
        }

        $musicCleared = false;

        if (array_key_exists('title', $payload)) {
            $title = is_string($payload['title']) ? trim($payload['title']) : null;
            if ($title === null || $title === '') {
                throw new AlbumException('Título do álbum não pode ser vazio.', 'TITLE_REQUIRED', 422);
            }
            if (mb_strlen($title) < 2) {
                throw new AlbumException('O título deve ter pelo menos 2 caracteres.', 'TITLE_TOO_SHORT', 422);
            }
            $album->setTitle($title);
        }
        if (array_key_exists('subtitle', $payload)) {
            $album->setSubtitle(is_string($payload['subtitle']) ? $payload['subtitle'] : null);
        }
        if (array_key_exists('category', $payload)) {
            $category = is_string($payload['category']) ? trim($payload['category']) : '';
            if ($category !== '') {
                $parsed = AlbumCategory::tryFrom($category);
                if ($parsed === null) {
                    throw new AlbumException('Categoria do álbum inválida.', 'INVALID_CATEGORY', 422);
                }
                $album->setCategory($parsed);
            }
        }
        if (array_key_exists('honoree_names', $payload)) {
            $album->setHonoreeNames(is_string($payload['honoree_names']) ? $payload['honoree_names'] : null);
        }
        if (array_key_exists('dedication', $payload)) {
            $album->setClosingMessage(is_string($payload['dedication']) ? $payload['dedication'] : null);
        }
        if (array_key_exists('closing_message', $payload)) {
            $album->setClosingMessage(is_string($payload['closing_message']) ? $payload['closing_message'] : null);
        }
        if (array_key_exists('signature', $payload)) {
            $album->setSignature(is_string($payload['signature']) ? $payload['signature'] : null);
        }
        if (array_key_exists('color_primary', $payload)) {
            $album->setColorPrimary(is_string($payload['color_primary']) ? $payload['color_primary'] : null);
        }
        if (array_key_exists('is_public', $payload)) {
            $album->setIsPublic((bool) $payload['is_public']);
        }
        if (array_key_exists('presentation', $payload)) {
            $presentation = BookPresentation::tryFromId((string) $payload['presentation']);
            if ($presentation === null) {
                throw new AlbumException('Apresentação do livro inválida.', 'INVALID_PRESENTATION', 422);
            }
            $album->setPresentation($presentation);
        }
        if (array_key_exists('photos_per_page', $payload)) {
            $perPage = (int) $payload['photos_per_page'];
            if ($perPage < 1 || $perPage > 4) {
                throw new AlbumException(
                    'Fotos por página deve ser entre 1 e 4.',
                    'INVALID_PHOTOS_PER_PAGE',
                    422,
                );
            }
            $album->setPhotosPerPage($perPage);
        }

        if (array_key_exists('book_config', $payload)) {
            if ($payload['book_config'] === null) {
                $album->setBookConfig(null);
                $musicCleared = $this->clearMusicMedia($album);
            } elseif (is_array($payload['book_config'])) {
                /** @var array<string, mixed> $config */
                $config = $payload['book_config'];
                $this->assertMaxDepth($config, 5);
                $encoded = json_encode($config);
                if ($encoded === false || strlen($encoded) > 65_536) {
                    throw new AlbumException('book_config excede o tamanho máximo permitido.', 'PAYLOAD_TOO_LARGE', 413);
                }
                $album->setBookConfig($config);
                if (isset($config['colors']) && is_array($config['colors']) && isset($config['colors']['accent'])) {
                    $accent = $config['colors']['accent'];
                    if (is_string($accent) && $accent !== '') {
                        $album->setColorPrimary($accent);
                    }
                }
                $music = $config['music'] ?? null;
                if (is_array($music) && (($music['source'] ?? '') === 'none')) {
                    $musicCleared = $this->clearMusicMedia($album);
                }
            } else {
                throw new AlbumException('book_config inválido.', 'INVALID_BOOK_CONFIG', 422);
            }
        }

        if (array_key_exists('book_pages', $payload)) {
            if ($payload['book_pages'] === null) {
                $album->setBookPages(null);
            } elseif (is_array($payload['book_pages'])) {
                if (count($payload['book_pages']) > 60) {
                    throw new AlbumException('book_pages excede o limite de 60 entradas.', 'PAYLOAD_TOO_LARGE', 413);
                }
                /** @var list<array<string, mixed>> $bookPages */
                $bookPages = [];
                foreach ($payload['book_pages'] as $page) {
                    if (is_array($page)) {
                        $this->assertMaxDepth($page, 3);
                        $bookPages[] = $page;
                    }
                }
                $album->setBookPages($bookPages);
            } else {
                throw new AlbumException('book_pages inválido.', 'INVALID_BOOK_PAGES', 422);
            }
        }

        if (isset($payload['pages']) && is_array($payload['pages'])) {
            $this->syncPageMetadata($album, $payload['pages']);
        }

        $this->albumRepository->save($album);

        return ['album' => $album, 'music_cleared' => $musicCleared];
    }

    public function delete(Album $album): void
    {
        $album->softDelete();
        $this->albumRepository->save($album);
    }

    /**
     * Returns true if at least one audio track was removed.
     */
    private function clearMusicMedia(Album $album): bool
    {
        $cleared = false;
        foreach ($this->mediaRepository->findActiveByAlbum($album) as $media) {
            if ($media->getMediaType() === AlbumMediaType::Audio) {
                $media->softDelete();
                $this->mediaRepository->save($media);
                $cleared = true;
            }
        }
        if ($album->getMusicMediaId() !== null) {
            $album->setMusicMediaId(null);
            $cleared = true;
        }

        return $cleared;
    }

    /**
     * @param list<array<string, mixed>> $pages
     */
    private function syncPageMetadata(Album $album, array $pages): void
    {
        foreach ($pages as $page) {
            if (!is_array($page)) {
                continue;
            }
            $mediaId = isset($page['media_id']) ? (string) $page['media_id'] : '';
            if (!UuidValue::isValid($mediaId)) {
                continue;
            }
            $media = $this->mediaRepository->findActiveByIdAndAlbum(UuidValue::fromString($mediaId), $album);
            if ($media === null || $media->getMediaType() !== AlbumMediaType::Photo) {
                continue;
            }
            if (array_key_exists('title', $page)) {
                $media->setTitle(is_string($page['title']) ? $page['title'] : null);
            }
            if (array_key_exists('caption', $page)) {
                $media->setCaption(is_string($page['caption']) ? $page['caption'] : null);
            }
            if (array_key_exists('memory_date', $page)) {
                $media->setMemoryDate(is_string($page['memory_date']) ? $page['memory_date'] : null);
            }
            if (array_key_exists('place_name', $page)) {
                $media->setPlaceName(is_string($page['place_name']) ? $page['place_name'] : null);
            }
            $this->mediaRepository->save($media);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertMaxDepth(array $data, int $maxDepth, int $currentDepth = 0): void
    {
        if ($currentDepth >= $maxDepth) {
            throw new AlbumException('Estrutura JSON excede profundidade máxima permitida.', 'PAYLOAD_TOO_NESTED', 422);
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $this->assertMaxDepth($value, $maxDepth, $currentDepth + 1);
            }
        }
    }
}
