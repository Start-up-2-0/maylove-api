<?php

declare(strict_types=1);

namespace App\Application\Map;

use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Repository\MapPlaceRepository;

final class CoupleMapValidationService
{
    public function __construct(
        private readonly MapPlaceRepository $placeRepository,
    ) {
    }

    /**
     * @return array{valid: bool, errors: list<array<string, string>>, warnings: list<array<string, string>>}
     */
    public function validate(CoupleMap $map): array
    {
        $errors = [];
        $warnings = [];

        if (trim($map->getTitle()) === '') {
            $errors[] = $this->issue('title', 'REQUIRED', 'Título é obrigatório.');
        }

        if (trim($map->getCoupleNames()) === '') {
            $errors[] = $this->issue('couple_names', 'REQUIRED', 'Nomes do casal são obrigatórios.');
        }

        $placeCount = $this->placeRepository->countActiveByMap($map);
        if ($placeCount < 2) {
            $errors[] = $this->issue('places', 'MIN_PLACES', 'Adicione ao menos 2 locais.');
        }

        $hasHighlight = false;
        foreach ($this->placeRepository->findActiveByMap($map) as $place) {
            if ($place->getDescription() === null || trim($place->getDescription()) === '') {
                $warnings[] = $this->issue(
                    'places['.$place->getId().'].description',
                    'EMPTY_MEMORY',
                    sprintf('"%s" está sem descrição.', $place->getTitle()),
                );
            }

            if ($place->isHighlight()) {
                $hasHighlight = true;
            }
        }

        if (!$hasHighlight && $placeCount > 0) {
            $warnings[] = $this->issue('places', 'NO_HIGHLIGHT', 'Nenhum local marcado como destaque.');
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /** @return array<string, string> */
    private function issue(string $field, string $code, string $message): array
    {
        return [
            'field' => $field,
            'code' => $code,
            'message' => $message,
        ];
    }
}
