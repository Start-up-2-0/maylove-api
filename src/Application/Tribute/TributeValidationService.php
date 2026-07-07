<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Domain\Tribute\MusicSource;
use App\Domain\Tribute\TributeEffects;
use App\Domain\Tribute\TributeMediaType;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Repository\TributeMediaRepository;

final class TributeValidationService
{
    public function __construct(
        private readonly TributeMediaRepository $mediaRepository,
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

        $message = trim(strip_tags((string) $tribute->getMessage()));
        if (mb_strlen($message) < 10) {
            $errors[] = $this->issue('message', 'MIN_LENGTH', 'A mensagem deve ter pelo menos 10 caracteres.');
        }

        $photoCount = $this->mediaRepository->countActiveByTributeAndType($tribute, TributeMediaType::Photo);
        if ($photoCount < 1) {
            $errors[] = $this->issue('photos', 'MIN_PHOTOS', 'É necessário pelo menos uma foto.');
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
