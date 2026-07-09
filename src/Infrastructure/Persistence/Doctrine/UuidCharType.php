<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

/**
 * Persiste UUIDs como CHAR(36), compatível com o schema MySQL das migrations.
 */
final class UuidCharType extends UuidType
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 36, 'fixed' => true]);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof AbstractUid) {
            return $value->toRfc4122();
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (!\is_string($value)) {
            throw new \InvalidArgumentException('UUID inválido.');
        }

        return Uuid::fromString($value)->toRfc4122();
    }
}
