<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Domain\Billing\Exception\BillingException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MercadoPagoClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $accessToken,
        private readonly string $apiBaseUrl,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->accessToken !== '';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createCheckoutPreference(array $payload): array
    {
        $this->assertConfigured();

        $response = $this->httpClient->request(
            'POST',
            $this->apiBaseUrl.'/checkout/preferences',
            [
                'headers' => $this->headers(),
                'json' => $payload,
            ],
        );

        return $this->decodeResponse($response->getContent(false), $response->getStatusCode());
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        $this->assertConfigured();

        $response = $this->httpClient->request(
            'GET',
            $this->apiBaseUrl.'/v1/payments/'.rawurlencode($paymentId),
            ['headers' => $this->headers()],
        );

        return $this->decodeResponse($response->getContent(false), $response->getStatusCode());
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreapproval(string $preapprovalId): array
    {
        $this->assertConfigured();

        $response = $this->httpClient->request(
            'GET',
            $this->apiBaseUrl.'/preapproval/'.rawurlencode($preapprovalId),
            ['headers' => $this->headers()],
        );

        return $this->decodeResponse($response->getContent(false), $response->getStatusCode());
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createPixPayment(array $payload, string $idempotencyKey): array
    {
        $this->assertConfigured();

        $response = $this->httpClient->request(
            'POST',
            $this->apiBaseUrl.'/v1/payments',
            [
                'headers' => array_merge($this->headers(), [
                    'X-Idempotency-Key' => $idempotencyKey,
                ]),
                'json' => $payload,
            ],
        );

        return $this->decodeResponse($response->getContent(false), $response->getStatusCode());
    }

    /**
     * Extrai dados do QR Pix da resposta de pagamento.
     *
     * @param array<string, mixed> $payment
     *
     * @return array{payment_id: string, qr_code: ?string, qr_code_base64: ?string, ticket_url: ?string}
     */
    public function extractPixData(array $payment): array
    {
        $poi = is_array($payment['point_of_interaction'] ?? null)
            ? $payment['point_of_interaction']
            : [];
        $tx = is_array($poi['transaction_data'] ?? null) ? $poi['transaction_data'] : [];

        $paymentId = isset($payment['id']) ? (string) $payment['id'] : '';

        return [
            'payment_id' => $paymentId,
            'qr_code' => isset($tx['qr_code']) && is_string($tx['qr_code']) ? $tx['qr_code'] : null,
            'qr_code_base64' => isset($tx['qr_code_base64']) && is_string($tx['qr_code_base64'])
                ? $tx['qr_code_base64']
                : null,
            'ticket_url' => isset($tx['ticket_url']) && is_string($tx['ticket_url'])
                ? $tx['ticket_url']
                : null,
        ];
    }

    public function resolveCheckoutUrl(array $preference): string
    {
        $sandbox = str_starts_with($this->accessToken, 'TEST-');
        $key = $sandbox ? 'sandbox_init_point' : 'init_point';
        $url = $preference[$key] ?? $preference['init_point'] ?? null;

        if (!is_string($url) || $url === '') {
            throw new BillingException('Mercado Pago não retornou URL de checkout.', 'PAYMENT_GATEWAY_ERROR', 502);
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(string $body, int $statusCode): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($body, true) ?? [];

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = is_string($decoded['message'] ?? null)
                ? (string) $decoded['message']
                : 'Erro ao comunicar com Mercado Pago.';

            throw new BillingException($message, 'PAYMENT_GATEWAY_ERROR', 502);
        }

        return $decoded;
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new BillingException(
                'Mercado Pago não configurado. Defina MERCADOPAGO_ACCESS_TOKEN.',
                'PAYMENT_NOT_CONFIGURED',
                503,
            );
        }
    }
}
