<?php

declare(strict_types=1);

namespace App\Application\Auth;

final class AuthSessionContext
{
    public function __construct(
        public readonly ?string $ip,
        public readonly ?string $userAgent,
    ) {
    }
}
