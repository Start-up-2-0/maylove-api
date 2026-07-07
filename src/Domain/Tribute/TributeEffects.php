<?php

declare(strict_types=1);

namespace App\Domain\Tribute;

final class TributeEffects
{
    /** @var list<string> */
    public const ALLOWED = [
        'confetti',
        'hearts',
        'petals',
        'fireworks',
        'stars',
        'snow',
    ];
}
