<?php

declare(strict_types=1);

namespace App\Application\Auth;

final class IssuedTokenPair
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly \DateTimeImmutable $accessTokenExpiresAt,
        public readonly \DateTimeImmutable $refreshTokenExpiresAt,
        public readonly string $sessionId,
    ) {
    }
}
