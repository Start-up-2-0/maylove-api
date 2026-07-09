<?php

declare(strict_types=1);

namespace App\Domain\Tribute;

enum MediaProcessingStatus: string
{
    case Ready = 'ready';
    case Processing = 'processing';
    case Failed = 'failed';
}
