<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Infrastructure\Persistence\Entity\AuthSession;
use App\Infrastructure\Persistence\Entity\User;

final class AuthenticatedSession
{
    public function __construct(
        public readonly AuthSession $session,
        public readonly User $user,
    ) {
    }
}
