<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Symfony\Component\Uid\Uuid;

/**
 * Aceita UUIDs em formato CHAR(36), inclusive os determinísticos do catálogo (md5),
 * que não passam em Uuid::isValid() por não seguirem RFC 4122.
 */
final class UuidValue
{
    private const FORMAT = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public static function isValid(string $value): bool
    {
        $value = trim($value);

        return $value !== '' && preg_match(self::FORMAT, $value) === 1;
    }

    public static function fromString(string $value): Uuid
    {
        if (!self::isValid($value)) {
            throw new \InvalidArgumentException(sprintf('UUID inválido: "%s".', $value));
        }

        return Uuid::fromString($value);
    }
}
