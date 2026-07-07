<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Infrastructure\Persistence\Entity\User;

final class AuthLoginResult
{
    public function __construct(
        public readonly IssuedTokenPair $tokens,
        public readonly User $user,
        public readonly bool $requiresEmailConfirmation,
    ) {
    }
}
