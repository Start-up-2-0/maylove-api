<?php

declare(strict_types=1);

namespace App\Domain\Album;

enum BookPresentation: string
{
    case FamilyAlbum = 'family-album';
    case Polaroid = 'polaroid';
    case MemoryNotebook = 'memory-notebook';
    case RomanticBook = 'romantic-book';
    case Timeline = 'timeline';
    case PhotoMagazine = 'photo-magazine';

    public function label(): string
    {
        return match ($this) {
            self::FamilyAlbum => 'Álbum de Família',
            self::Polaroid => 'Polaroid',
            self::MemoryNotebook => 'Caderno de Memórias',
            self::RomanticBook => 'Livro Romântico',
            self::Timeline => 'Linha do Tempo',
            self::PhotoMagazine => 'Revista Fotográfica',
        };
    }

    public static function tryFromId(string $id): ?self
    {
        return self::tryFrom($id);
    }

    public static function default(): self
    {
        return self::FamilyAlbum;
    }
}
