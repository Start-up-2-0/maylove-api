<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

final class HmacTokenCodec
{
    public function __construct(
        private readonly string $secret,
    ) {
        if (strlen($this->secret) < 32) {
            throw new \InvalidArgumentException('Token secret deve possuir ao menos 32 caracteres.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function encode(array $payload): string
    {
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $payloadEncoded = $this->base64UrlEncode($payloadJson);
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $payloadEncoded, $this->secret, true));

        return $payloadEncoded.'.'.$signature;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
