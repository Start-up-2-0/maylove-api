<?php

declare(strict_types=1);

namespace App\Domain\User;

enum UserStatus: string
{
    case PendingVerification = 'pending_verification';
    case Active = 'active';
    case Suspended = 'suspended';
}
