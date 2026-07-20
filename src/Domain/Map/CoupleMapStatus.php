<?php

declare(strict_types=1);

namespace App\Domain\Map;

enum CoupleMapStatus: string
{
    case Draft = 'draft';
    case AwaitingPayment = 'awaiting_payment';
    case Published = 'published';
    case Archived = 'archived';

    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::AwaitingPayment;
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
