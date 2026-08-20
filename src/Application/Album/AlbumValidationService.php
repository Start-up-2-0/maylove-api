<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Application\Media\MediaUploadPolicy;
use App\Domain\Album\AlbumMediaType;
use App\Domain\Album\AlbumCategory;
use App\Domain\Album\BookPresentation;
use App\Infrastructure\Persistence\Entity\Album;
use App\Infrastructure\Persistence\Entity\AlbumMedia;
use App\Infrastructure\Persistence\Repository\AlbumMediaRepository;
use App\Infrastructure\Persistence\Repository\AlbumChapterRepository;
use App\Infrastructure\Persistence\Repository\AlbumMemoryRepository;

final class AlbumValidationService
{
    public function __construct(
        private readonly AlbumMediaRepository $mediaRepository,
        private readonly AlbumChapterRepository $chapterRepository,
        private readonly AlbumMemoryRepository $memoryRepository,
    ) {
    }

    /**
     * @return array{valid: bool, errors: list<array<string, string>>, warnings: list<array<string, string>>}
     */
    public function validate(Album $album): array
    {
        $errors = [];
        $warnings = [];

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

        $isMemorial = in_array($album->getCategory(), [AlbumCategory::Memorial, AlbumCategory::Posthumous], true)
            || $album->getPresentation() === BookPresentation::MemorialLuz;

        if ($isMemorial) {
            if (trim((string) $album->getHonoreeNames()) === '') {
                $errors[] = $this->issue('honoree_names', 'MEMORIAL_HONOREE_REQUIRED', 'Informe o nome do homenageado.');
            }
            if (mb_strlen(trim((string) $album->getClosingMessage())) < 20) {
                $errors[] = $this->issue('dedication', 'MEMORIAL_BIOGRAPHY_REQUIRED', 'Escreva uma biografia com pelo menos 20 caracteres.');
            }
            if ($this->chapterRepository->findOrderedByAlbum($album) === []) {
                $errors[] = $this->issue('chapters', 'MEMORIAL_CHAPTER_REQUIRED', 'Crie ao menos um capítulo para organizar a homenagem.');
            }
            if ($this->memoryRepository->findOrderedByAlbum($album) === []) {
                $errors[] = $this->issue('memories', 'MEMORIAL_MEMORY_REQUIRED', 'Adicione ao menos uma memória ao memorial.');
            }
            $lifeDates = $album->getContentJson()['life_dates'] ?? null;
            if (is_array($lifeDates)) {
                $birthDate = is_string($lifeDates['birth_date'] ?? null) ? $lifeDates['birth_date'] : '';
                $deathDate = is_string($lifeDates['death_date'] ?? null) ? $lifeDates['death_date'] : '';
                if ($birthDate !== '' && $deathDate !== '' && $deathDate < $birthDate) {
                    $errors[] = $this->issue('life_dates', 'MEMORIAL_LIFE_DATES_INVALID', 'A data de falecimento não pode ser anterior à data de nascimento.');
                }
            }
        } else {
            $photosWithoutContext = array_filter(
                $this->mediaRepository->findActiveByAlbum($album),
                static fn (AlbumMedia $media): bool => $media->getMediaType() === AlbumMediaType::Photo
                    && trim((string) $media->getTitle()) === ''
                    && trim((string) $media->getCaption()) === '',
            );
            if ($photosWithoutContext !== []) {
                $warnings[] = $this->issue('photos', 'PHOTO_CONTEXT_RECOMMENDED', 'Adicione títulos ou legendas para transformar a galeria em uma história.');
            }
        }

        $bookConfig = $album->getBookConfig();
        $coverId = is_array($bookConfig) && isset($bookConfig['cover']) && is_array($bookConfig['cover'])
            ? ($bookConfig['cover']['media_id'] ?? null)
            : null;
        if (!is_string($coverId) || trim($coverId) === '') {
            $warnings[] = $this->issue('photos', 'COVER_RECOMMENDED', 'Escolha uma foto de capa para melhorar a primeira impressão.');
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
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
