<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Auth\MayLoveTokenMetadata;
use App\Domain\User\UserRole;
use App\Infrastructure\Persistence\Entity\User;

final class MayLoveTokenService
{
    private readonly string $saltKey;

    public function __construct(
        private readonly string $tokenSalt,
        private readonly int $sessionMinutes,
        private readonly int $refreshTokenDays,
    ) {
        if (strlen($this->tokenSalt) < 32) {
            throw new \InvalidArgumentException('AUTH_TOKEN_SALT deve possuir ao menos 32 caracteres.');
        }

        $this->saltKey = $this->tokenSalt;
    }

    public function issueAccessToken(User $user, string $sessionId, \DateTimeImmutable $issuedAt): string
    {
        $payload = [
            'uid' => (string) $user->getId(),
            'sid' => $sessionId,
            'iat' => $issuedAt->getTimestamp(),
            'nonce' => bin2hex(random_bytes(8)),
            'role' => $user->getRole()->value,
        ];

        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $payloadEncoded = $this->base64UrlEncode($payloadJson);
        $signature = $this->base64UrlEncode($this->computeHmac($payloadEncoded));

        return $payloadEncoded.'.'.$signature;
    }

    public function validateMetadata(string $token): ?MayLoveTokenMetadata
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadEncoded, $signature] = $parts;
        $expectedSignature = $this->base64UrlEncode($this->computeHmac($payloadEncoded));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        try {
            $payloadJson = $this->base64UrlDecode($payloadEncoded);
            /** @var array{uid?: string, sid?: string, iat?: int, role?: string} $payload */
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

            if (
                !isset($payload['uid'], $payload['sid'], $payload['iat'], $payload['role'])
                || !is_string($payload['uid'])
                || !is_string($payload['sid'])
                || !is_int($payload['iat'])
                || !is_string($payload['role'])
            ) {
                return null;
            }

            $role = UserRole::tryFrom($payload['role']);
            if ($role === null) {
                return null;
            }

            return new MayLoveTokenMetadata(
                $payload['uid'],
                $payload['sid'],
                $payload['iat'],
                $role,
            );
        } catch (\JsonException) {
            return null;
        }
    }

    public function generateRefreshToken(): string
    {
        return $this->base64UrlEncode(random_bytes(64));
    }

    public function hashToken(string $token): string
    {
        return bin2hex($this->computeHmac($token));
    }

    public function getAccessTokenExpiration(\DateTimeImmutable $issuedAt): \DateTimeImmutable
    {
        return $issuedAt->modify(sprintf('+%d minutes', $this->sessionMinutes));
    }

    public function getRefreshTokenExpiration(\DateTimeImmutable $issuedAt): \DateTimeImmutable
    {
        return $issuedAt->modify(sprintf('+%d days', $this->refreshTokenDays));
    }

    public function isExpired(MayLoveTokenMetadata $metadata, \DateTimeImmutable $now): bool
    {
        $issuedAt = (new \DateTimeImmutable())->setTimestamp($metadata->issuedAtUnix);

        return $this->getAccessTokenExpiration($issuedAt) <= $now;
    }

    private function computeHmac(string $value): string
    {
        return hash_hmac('sha256', $value, $this->saltKey, true);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padded = strtr($value, '-_', '+/');
        $mod = strlen($padded) % 4;
        if ($mod !== 0) {
            $padded .= str_repeat('=', 4 - $mod);
        }

        $decoded = base64_decode($padded, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Payload inválido.');
        }

        return $decoded;
    }
}
