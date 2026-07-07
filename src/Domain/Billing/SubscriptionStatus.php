<?php

declare(strict_types=1);

namespace App\Domain\Billing;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';

    public function isActiveBenefit(): bool
    {
        return $this === self::Active || $this === self::PastDue;
    }
}
