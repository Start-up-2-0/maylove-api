<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

final class MercadoPagoWebhookSignatureValidator
{
    private const MAX_SKEW_SECONDS = 300;

    public function __construct(
        private readonly string $webhookSecret,
    ) {
    }

    public function validate(?string $signatureHeader, ?string $requestId, string $payload): bool
    {
        if ($this->webhookSecret === '') {
            return false;
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $timestamp = '';
        $signature = '';
        foreach (explode(',', $signatureHeader) as $part) {
            $kv = array_map('trim', explode('=', $part, 2));
            if (count($kv) !== 2) {
                continue;
            }
            if (strcasecmp($kv[0], 'ts') === 0) {
                $timestamp = $kv[1];
            }
            if (strcasecmp($kv[0], 'v1') === 0) {
                $signature = $kv[1];
            }
        }

        if ($timestamp === '' || $signature === '' || !ctype_digit($timestamp)) {
            return false;
        }

        $eventTime = (int) $timestamp;
        if (abs(time() - $eventTime) > self::MAX_SKEW_SECONDS) {
            return false;
        }

        $dataId = $this->extractDataId($payload);
        if ($dataId === null || $dataId === '') {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, $requestId ?? '', $timestamp);
        $expected = hash_hmac('sha256', $manifest, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    private function extractDataId(string $payload): ?string
    {
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return null;
        }

        if (isset($decoded['data']) && is_array($decoded['data']) && isset($decoded['data']['id'])) {
            return $this->stringify($decoded['data']['id']);
        }

        if (isset($decoded['id'])) {
            return $this->stringify($decoded['id']);
        }

        return null;
    }

    private function stringify(mixed $value): ?string
    {
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }

        return null;
    }
}
