<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabela rate_limit_entries para rate limiting persistido no banco.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE rate_limit_entries (
                `key`      VARCHAR(180)  NOT NULL,
                count      INT UNSIGNED  NOT NULL DEFAULT 1,
                expires_at DATETIME      NOT NULL,
                PRIMARY KEY (`key`),
                INDEX idx_rate_limit_expires (expires_at)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rate_limit_entries');
    }
}
