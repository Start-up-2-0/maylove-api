<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabelas do módulo Álbum (albums e album_media).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE albums (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                status VARCHAR(30) NOT NULL,
                title VARCHAR(200) DEFAULT NULL,
                subtitle VARCHAR(200) DEFAULT NULL,
                closing_message LONGTEXT DEFAULT NULL,
                signature VARCHAR(120) DEFAULT NULL,
                color_primary CHAR(7) DEFAULT NULL,
                music_media_id CHAR(36) DEFAULT NULL,
                is_public TINYINT(1) NOT NULL DEFAULT 1,
                views_count INT UNSIGNED NOT NULL DEFAULT 0,
                published_at DATETIME(6) DEFAULT NULL,
                deleted_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_albums_slug (slug),
                INDEX idx_albums_user_status (user_id, status, updated_at),
                PRIMARY KEY (id),
                CONSTRAINT fk_albums_user FOREIGN KEY (user_id) REFERENCES users (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE album_media (
                id CHAR(36) NOT NULL,
                album_id CHAR(36) NOT NULL,
                storage_file_id VARCHAR(64) NOT NULL,
                media_type VARCHAR(20) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) DEFAULT NULL,
                size_bytes INT UNSIGNED DEFAULT NULL,
                sort_order SMALLINT NOT NULL DEFAULT 0,
                title VARCHAR(200) DEFAULT NULL,
                caption LONGTEXT DEFAULT NULL,
                url_thumbnail VARCHAR(500) DEFAULT NULL,
                deleted_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                INDEX idx_album_media_album (album_id, sort_order),
                PRIMARY KEY (id),
                CONSTRAINT fk_album_media_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE album_media');
        $this->addSql('DROP TABLE albums');
    }
}
