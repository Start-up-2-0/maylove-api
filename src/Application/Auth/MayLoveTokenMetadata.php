<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\User\UserRole;

final class MayLoveTokenMetadata
{
    public function __construct(
        public readonly string $userId,
        public readonly string $sessionId,
        public readonly int $issuedAtUnix,
        public readonly UserRole $role,
    ) {
    }
}
