<?php

declare(strict_types=1);

namespace App\Domain\Album;

enum Sentiment: string
{
    case Happy = 'feliz';
    case Longing = 'saudade';
    case Love = 'amor';
    case Gratitude = 'gratidao';
    case Pride = 'orgulho';
    case Peace = 'paz';
    case Other = 'outro';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
