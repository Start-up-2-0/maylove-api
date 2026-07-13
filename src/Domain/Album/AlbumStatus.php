<?php

declare(strict_types=1);

namespace App\Domain\Album;

enum AlbumStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
