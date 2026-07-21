<?php

declare(strict_types=1);

namespace App\Application\Bouquet;

use App\Infrastructure\Persistence\Entity\DigitalBouquet;

final class DigitalBouquetValidationService
{
    /**
     * @return array{valid: bool, errors: list<array<string, string>>, warnings: list<array<string, string>>}
     */
    public function validate(DigitalBouquet $bouquet): array
    {
        $errors = [];
        $warnings = [];

        $stemCount = count($bouquet->getStemsJson());
        if ($stemCount < 1) {
            $errors[] = $this->issue('stems', 'MIN_STEMS', 'Escolha ao menos 1 flor.');
        }

        if ($stemCount > DigitalBouquet::MAX_STEMS) {
            $errors[] = $this->issue('stems', 'MAX_STEMS', 'Máximo de 7 flores.');
        }

        if (trim($bouquet->getRecipientName()) === '') {
            $errors[] = $this->issue('recipient_name', 'REQUIRED', 'Nome de quem recebe é obrigatório.');
        }

        if (trim($bouquet->getSenderName()) === '') {
            $errors[] = $this->issue('sender_name', 'REQUIRED', 'Seu nome é obrigatório.');
        }

        if (trim($bouquet->getLetterBody()) === '') {
            $errors[] = $this->issue('letter_body', 'REQUIRED', 'Escreva a carta.');
        }

        if (mb_strlen(trim($bouquet->getLetterBody())) < 10) {
            $warnings[] = $this->issue('letter_body', 'SHORT_LETTER', 'A carta está bem curta.');
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
