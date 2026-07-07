<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabela tribute_views para analytics de visualizações.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE tribute_views (
                id CHAR(36) NOT NULL,
                tribute_id CHAR(36) NOT NULL,
                session_id VARCHAR(64) NOT NULL,
                viewed_at DATETIME(6) NOT NULL,
                INDEX idx_tribute_views_tribute (tribute_id, viewed_at),
                PRIMARY KEY (id),
                CONSTRAINT fk_tribute_views_tribute FOREIGN KEY (tribute_id) REFERENCES tributes (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tribute_views');
    }
}
