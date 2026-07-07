<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

final class MayLoveServiceTokenService
{
    private const ISSUER = 'maylove-api';
    private const AUDIENCE = 'maylove-storages';
    private const DEFAULT_TTL_SECONDS = 300;

    public function __construct(
        private readonly HmacTokenCodec $codec,
        private readonly int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ) {
    }

    public function issue(string $scope = 'storage:write'): string
    {
        $now = time();

        return $this->codec->encode([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'scope' => $scope,
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ]);
    }
}
