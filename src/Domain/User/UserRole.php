<?php

declare(strict_types=1);

namespace App\Domain\User;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
