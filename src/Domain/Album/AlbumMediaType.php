<?php

declare(strict_types=1);

namespace App\Domain\Album;

enum AlbumMediaType: string
{
    case Photo = 'photo';
    case Audio = 'audio';
}
