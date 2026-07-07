<?php

declare(strict_types=1);

namespace App\Domain\Tribute;

enum TributeMediaType: string
{
    case Photo = 'photo';
    case Video = 'video';
    case Audio = 'audio';
}
