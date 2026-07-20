<?php

declare(strict_types=1);

namespace App\Domain\Album;

enum ExperienceType: string
{
    case Bouquet = 'bouquet';
    case Map = 'map';
    case StarrySky = 'starry_sky';
    case Surprise = 'surprise';
    case Game = 'game';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
