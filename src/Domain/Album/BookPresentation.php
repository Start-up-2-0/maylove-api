<?php

declare(strict_types=1);

namespace App\Domain\Album;

enum BookPresentation: string
{
    case ClassicPhotobook = 'classic-photobook';
    case WeddingBook = 'wedding-book';
    case FamilyMemories = 'family-memories';
    case PolaroidMemories = 'polaroid-memories';
    case PolaroidBoard = 'polaroid-board';
    case PortraitAlbum = 'portrait-album';
    case InstantPhoto = 'instant-photo';
    case Scrapbook = 'scrapbook';
    case TravelJournal = 'travel-journal';
    case MagazineStyle = 'magazine-style';
    case LuxuryAlbum = 'luxury-album';
    case Timeline = 'timeline';

    /** @deprecated slugs legados — mantidos para álbuns existentes */
    case FamilyAlbum = 'family-album';
    case Polaroid = 'polaroid';
    case MemoryNotebook = 'memory-notebook';
    case RomanticBook = 'romantic-book';
    case PhotoMagazine = 'photo-magazine';

    public function label(): string
    {
        return match ($this) {
            self::ClassicPhotobook => 'Memory Book',
            self::WeddingBook => 'Wedding Book',
            self::FamilyMemories => 'Family Memories',
            self::PolaroidMemories => 'Polaroid Memories',
            self::PolaroidBoard => 'Quadro Polaroid',
            self::PortraitAlbum => 'Álbum Retrato',
            self::InstantPhoto => 'Instant Photo',
            self::Scrapbook => 'Scrapbook',
            self::TravelJournal => 'Travel Journal',
            self::MagazineStyle => 'Magazine Style',
            self::LuxuryAlbum => 'Luxury Album',
            self::Timeline => 'Linha do Tempo',
            self::FamilyAlbum => 'Family Memories',
            self::Polaroid => 'Polaroid Memories',
            self::MemoryNotebook => 'Travel Journal',
            self::RomanticBook => 'Wedding Book',
            self::PhotoMagazine => 'Magazine Style',
        };
    }

    public static function tryFromId(string $id): ?self
    {
        return self::tryFrom($id);
    }

    public static function default(): self
    {
        return self::ClassicPhotobook;
    }
}
