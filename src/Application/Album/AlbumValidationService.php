<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Application\Media\MediaUploadPolicy;
use App\Domain\Album\AlbumMediaType;
use App\Domain\Album\BookPresentation;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
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

        if ($album->getPresentation() === BookPresentation::Timeline) {
            $errors = [...$errors, ...$this->validateTimelinePhotos($album)];
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function validateTimelinePhotos(Album $album): array
    {
        $errors = [];
        $index = 0;

        foreach ($this->mediaRepository->findActiveByAlbum($album) as $media) {
            if ($media->getMediaType() !== AlbumMediaType::Photo) {
                continue;
            }

            ++$index;
            $label = sprintf('Foto %d', $index);
            $errors = [...$errors, ...$this->validateTimelinePhoto($media, $label)];
        }

        return $errors;
    }

    /**
     * @return list<array<string, string>>
     */
    private function validateTimelinePhoto(AlbumMedia $media, string $label): array
    {
        $errors = [];
        $memoryDate = trim((string) $media->getMemoryDate());
        $title = trim((string) $media->getTitle());
        $caption = trim((string) $media->getCaption());

        if ($memoryDate === '') {
            $errors[] = $this->issue('photos', 'TIMELINE_DATE_REQUIRED', sprintf('%s: informe a data do momento.', $label));
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $memoryDate)) {
            $errors[] = $this->issue('photos', 'TIMELINE_DATE_INVALID', sprintf('%s: use uma data válida.', $label));
        }

        if ($title === '') {
            $errors[] = $this->issue('photos', 'TIMELINE_TITLE_REQUIRED', sprintf('%s: informe o título do momento.', $label));
        }

        if ($caption === '') {
            $errors[] = $this->issue('photos', 'TIMELINE_DESCRIPTION_REQUIRED', sprintf('%s: informe a descrição do momento.', $label));
        }

        return $errors;
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
