<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Infrastructure\Security\MayLoveServiceTokenService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class StorageApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly MayLoveServiceTokenService $serviceTokenService,
        private readonly string $storageApiUrl,
    ) {
    }

    public function health(): bool
    {
        $response = $this->httpClient->request(
            'GET',
            $this->baseUrl().'/api/v1/health',
            ['timeout' => 3],
        );

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $payload = $response->toArray(false);

        return ($payload['status'] ?? null) === 'ok';
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createUploadTicket(array $payload): array
    {
        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl().'/internal/upload-ticket',
            [
                'headers' => $this->serviceHeaders(),
                'json' => $payload,
            ],
        );

        $body = $response->toArray(false);

        return $body['data'] ?? $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmFile(string $fileId, string $mediaType, string $mimeType): array
    {
        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl().'/internal/files/'.$fileId.'/confirm',
            [
                'headers' => $this->serviceHeaders(),
                'json' => [
                    'media_type' => $mediaType,
                    'mime_type' => $mimeType,
                ],
            ],
        );

        $body = $response->toArray(false);

        return $body['data'] ?? $body;
    }

    public function deleteFile(string $fileId): void
    {
        $this->httpClient->request(
            'DELETE',
            $this->baseUrl().'/internal/files/'.$fileId,
            ['headers' => $this->serviceHeaders()],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getFileUrl(string $fileId, string $visibility = 'protected', int $ttl = 3600): array
    {
        $response = $this->httpClient->request(
            'GET',
            $this->baseUrl().'/internal/files/'.$fileId.'/url',
            [
                'headers' => $this->serviceHeaders(),
                'query' => [
                    'visibility' => $visibility,
                    'ttl' => $ttl,
                ],
            ],
        );

        $body = $response->toArray(false);

        return $body['data'] ?? $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function generateOgImage(
        string $tributeId,
        ?string $sourceFileId,
        string $title,
        string $colorPrimary,
    ): array {
        $response = $this->httpClient->request(
            'POST',
            $this->baseUrl().'/internal/og-image',
            [
                'headers' => $this->serviceHeaders(),
                'json' => [
                    'tribute_id' => $tributeId,
                    'source_file_id' => $sourceFileId,
                    'title' => $title,
                    'color_primary' => $colorPrimary,
                ],
            ],
        );

        $body = $response->toArray(false);

        return $body['data'] ?? $body;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listFiles(string $context, string $contextId): array
    {
        $response = $this->httpClient->request(
            'GET',
            $this->baseUrl().'/internal/files',
            [
                'headers' => $this->serviceHeaders(),
                'query' => [
                    'context' => $context,
                    'context_id' => $contextId,
                ],
            ],
        );

        $body = $response->toArray(false);

        return $body['data'] ?? [];
    }

    /**
     * @return array<string, string>
     */
    private function serviceHeaders(): array
    {
        return [
            'x-maylove-service-token' => $this->serviceTokenService->issue(),
            'Content-Type' => 'application/json',
        ];
    }

    private function baseUrl(): string
    {
        return rtrim($this->storageApiUrl, '/');
    }
}
