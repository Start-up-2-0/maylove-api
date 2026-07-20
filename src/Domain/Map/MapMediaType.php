<?php

declare(strict_types=1);

namespace App\Domain\Map;

enum MapMediaType: string
{
    case Photo = 'photo';
    case Video = 'video';
}
