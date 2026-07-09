<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Application\Wizard\PresentationSchemaProvider;
use App\Domain\Tribute\MusicSource;
use App\Domain\Tribute\TributeEffects;
use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;

final class TributeValidationService
{
    public function __construct(
        private readonly TributeMediaRepository $mediaRepository,
        private readonly PresentationSchemaProvider $schemaProvider,
    ) {
    }

    /**
     * @return array{valid: bool, errors: list<array<string, string>>, warnings: list<array<string, string>>}
     */
    public function validate(Tribute $tribute): array
    {
        $errors = [];
        $warnings = [];

        $honoreeName = trim((string) $tribute->getHonoreeName());
        if ($honoreeName === '') {
            $errors[] = $this->issue('honoree_name', 'REQUIRED', 'Nome do homenageado é obrigatório.');
        }

        $content = $tribute->getContentJson();
        $message = trim(strip_tags((string) $tribute->getMessage()));
        $presentation = is_string($content['presentation'] ?? null) ? $content['presentation'] : null;
        $requirements = $this->schemaProvider->requirementsFor($presentation);

        if (!$requirements['usesMoments'] && mb_strlen($message) < 10) {
            $errors[] = $this->issue('message', 'MIN_LENGTH', 'A mensagem deve ter pelo menos 10 caracteres.');
        }

        // Requisitos de mídia dependem do estilo de apresentação escolhido: alguns
        // (ex.: Carta Simples) tornam fotos opcionais; layouts narrativos usam
        // "Momentos" e aceitam fotos ou momentos.
        $photoCount = $this->mediaRepository->countActiveByTributeAndType($tribute, TributeMediaType::Photo);

        if ($requirements['usesMoments']) {
            $filledMoments = 0;
            if (is_array($content['timeline'] ?? null)) {
                foreach ($content['timeline'] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $title = isset($item['title']) && is_string($item['title']) ? trim($item['title']) : '';
                    $description = isset($item['description']) && is_string($item['description'])
                        ? trim(strip_tags($item['description']))
                        : '';
                    if ($title !== '' || $description !== '') {
                        ++$filledMoments;
                    }
                }
            }
            if ($filledMoments < 1 && $photoCount < 1) {
                $errors[] = $this->issue('moments', 'MIN_MOMENTS', 'Adicione ao menos um trecho com texto ou uma foto.');
            }
        } elseif ($requirements['minPhotos'] > 0 && $photoCount < $requirements['minPhotos']) {
            $errors[] = $this->issue(
                'photos',
                'MIN_PHOTOS',
                sprintf('É necessário pelo menos %d foto(s) para este estilo.', $requirements['minPhotos']),
            );
        } elseif ($photoCount < 1) {
            $warnings[] = $this->issue('photos', 'NO_PHOTOS', 'Nenhuma foto adicionada.');
        }

        if ($tribute->getMusicSource() === MusicSource::None) {
            $warnings[] = $this->issue('music', 'MISSING_MUSIC', 'Nenhuma trilha sonora selecionada.');
        }

        if ($tribute->getMusicSource() === MusicSource::Library && $tribute->getMusicTrackId() === null) {
            $errors[] = $this->issue('music_track_id', 'REQUIRED', 'Selecione uma faixa da biblioteca.');
        }

        if ($tribute->getMusicSource() === MusicSource::Upload && $tribute->getMusicMediaId() === null) {
            $errors[] = $this->issue('music_media_id', 'REQUIRED', 'Envie um arquivo de áudio para a trilha.');
        }

        $effects = $tribute->getContentJson()['effects'] ?? [];
        if (is_array($effects)) {
            foreach ($effects as $effect) {
                if (!is_string($effect) || !in_array($effect, TributeEffects::ALLOWED, true)) {
                    $errors[] = $this->issue('content_json.effects', 'INVALID_EFFECT', 'Efeito visual inválido: '.$effect);
                }
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => $warnings,
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
