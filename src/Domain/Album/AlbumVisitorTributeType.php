<?php

declare(strict_types=1);

namespace App\Domain\Album;

enum AlbumVisitorTributeType: string
{
    case Candle = 'candle';
    case Flower = 'flower';
    case Message = 'message';

    public function ttlDays(): ?int
    {
        return match ($this) {
            self::Candle => 7,
            self::Flower => 10,
            self::Message => null,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
