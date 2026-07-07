<?php

declare(strict_types=1);

namespace App\Domain\Billing;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Refunded = 'refunded';
}
