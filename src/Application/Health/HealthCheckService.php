<?php

declare(strict_types=1);

namespace App\Application\Health;

use App\Infrastructure\Storage\StorageApiClient;
use Doctrine\DBAL\Connection;

final class HealthCheckService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly StorageApiClient $storageApiClient,
    ) {
    }

    /**
     * @return array{status: string, service: string, checks: array<string, string>}
     */
    public function run(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage_api' => $this->checkStorageApi(),
        ];

        $hasFailure = in_array('error', $checks, true);

        return [
            'status' => $hasFailure ? 'degraded' : 'ok',
            'service' => 'maylove-api',
            'checks' => $checks,
        ];
    }

    private function checkDatabase(): string
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkStorageApi(): string
    {
        try {
            return $this->storageApiClient->health() ? 'ok' : 'error';
        } catch (\Throwable) {
            return 'error';
        }
    }
}
