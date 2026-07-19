<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

use Doctrine\DBAL\Connection;

/**
 * Rate limiter persistido no banco MySQL.
 *
 * Substitui o InMemoryRateLimiter para suportar múltiplas instâncias
 * (Railway scale-out, restarts) sem perda de estado.
 *
 * Tabela gerenciada por esta classe — criada automaticamente se não existir.
 */
final class DbRateLimiter
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Retorna o contador atual da chave (0 se expirada ou inexistente).
     */
    public function get(string $key): int
    {
        $result = $this->connection->fetchOne(
            'SELECT count FROM rate_limit_entries WHERE `key` = ? AND expires_at > NOW()',
            [$key],
        );

        return $result === false ? 0 : (int) $result;
    }

    /**
     * Incrementa o contador. Cria a entrada se não existir ou se estiver expirada.
     * Retorna o novo valor do contador.
     */
    public function increment(string $key, int $windowSeconds): int
    {
        $this->connection->executeStatement(
            'INSERT INTO rate_limit_entries (`key`, count, expires_at)
             VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND))
             ON DUPLICATE KEY UPDATE
               count     = IF(expires_at <= NOW(), 1, count + 1),
               expires_at = IF(expires_at <= NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND), expires_at)',
            [$key, $windowSeconds, $windowSeconds],
        );

        return $this->get($key);
    }

    /**
     * Tenta "reservar" a chave por ttlSeconds.
     * Retorna true se reservou (a chave não existia ou estava expirada),
     * false se já existia e ainda está dentro da janela.
     */
    public function claim(string $key, int $ttlSeconds): bool
    {
        $rows = $this->connection->executeStatement(
            'INSERT IGNORE INTO rate_limit_entries (`key`, count, expires_at)
             SELECT ?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND)
             FROM DUAL
             WHERE NOT EXISTS (
               SELECT 1 FROM rate_limit_entries WHERE `key` = ? AND expires_at > NOW()
             )',
            [$key, $ttlSeconds, $key],
        );

        return $rows > 0;
    }
}
