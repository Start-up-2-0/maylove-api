<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Domain\Tribute\Exception\TributeException;

final class TributeProposalContentNormalizer
{
    private const MAX_LENGTH = 300;

    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $incoming
     *
     * @return array<string, mixed>
     */
    public function normalize(array $content, array $incoming): array
    {
        foreach (['question', 'celebration'] as $key) {
            if (!array_key_exists($key, $incoming)) {
                continue;
            }

            $value = $incoming[$key];
            if ($value === null || $value === '') {
                unset($content[$key]);
                continue;
            }
            if (!is_string($value)) {
                throw new TributeException(sprintf('Valor inválido para %s.', $key), 'VALIDATION_ERROR', 400);
            }

            $normalized = mb_substr(trim($value), 0, self::MAX_LENGTH);
            if ($normalized === '') {
                unset($content[$key]);
            } else {
                $content[$key] = $normalized;
            }
        }

        return $content;
    }
}
