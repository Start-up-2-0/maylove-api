<?php

declare(strict_types=1);

namespace App\Domain\Map;

enum MapStyle: string
{
    case Default = 'default';
    case Romantic = 'romantic';
    case Minimal = 'minimal';
    case Vintage = 'vintage';
}
