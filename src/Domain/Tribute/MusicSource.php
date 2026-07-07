<?php

declare(strict_types=1);

namespace App\Domain\Tribute;

enum MusicSource: string
{
    case None = 'none';
    case Library = 'library';
    case Upload = 'upload';
}
