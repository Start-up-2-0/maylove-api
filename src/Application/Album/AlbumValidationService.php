<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Application\Media\MediaUploadPolicy;
use App\Domain\Album\AlbumMediaType;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;

final class AlbumValidationService
{
    public function __construct(
        private readonly AlbumMediaRepository $mediaRepository,
    ) {
    }

    /**
     * @return array{valid: bool, errors: list<array<string, string>>}
     */
    public function validate(Album $album): array
    {
        $errors = [];

        $title = trim((string) $album->getTitle());
        if ($title === '') {
            $errors[] = $this->issue('title', 'REQUIRED', 'Título do álbum é obrigatório.');
        } elseif (mb_strlen($title) < 2) {
            $errors[] = $this->issue('title', 'MIN_LENGTH', 'O título deve ter pelo menos 2 caracteres.');
        }

        $photoCount = $this->mediaRepository->countActiveByAlbumAndType($album, AlbumMediaType::Photo);
        if ($photoCount < 1) {
            $errors[] = $this->issue('photos', 'MIN_PHOTOS', 'Adicione pelo menos uma foto ao álbum.');
        }

        if ($photoCount > MediaUploadPolicy::MAX_PHOTOS_PER_ALBUM) {
            $errors[] = $this->issue(
                'photos',
                'MAX_PHOTOS',
                sprintf('O álbum excede o limite de %d fotos.', MediaUploadPolicy::MAX_PHOTOS_PER_ALBUM),
            );
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function issue(string $field, string $code, string $message): array
    {
        return [
            'field' => $field,
            'code' => $code,
            'message' => $message,
        ];
    }
}
