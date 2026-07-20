<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Memorial: album_visitor_tributes para velas, flores e mensagens de visitantes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE album_visitor_tributes (
                id CHAR(36) NOT NULL,
                album_id CHAR(36) NOT NULL,
                type VARCHAR(16) NOT NULL,
                author_name VARCHAR(80) NOT NULL,
                message LONGTEXT NOT NULL,
                expires_at DATETIME(6) DEFAULT NULL,
                ip_hash VARCHAR(64) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                PRIMARY KEY(id),
                INDEX idx_album_tributes_album_created (album_id, created_at),
                CONSTRAINT FK_album_tributes_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE album_visitor_tributes');
    }
}
