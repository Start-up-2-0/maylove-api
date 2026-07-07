<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabelas de homenagens (tributes) e mídias (tribute_media).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE tributes (
                id CHAR(36) NOT NULL,
                user_id CHAR(36) NOT NULL,
                tribute_type_id CHAR(36) NOT NULL,
                template_id CHAR(36) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                status VARCHAR(30) NOT NULL,
                title VARCHAR(200) DEFAULT NULL,
                subtitle VARCHAR(200) DEFAULT NULL,
                honoree_name VARCHAR(120) DEFAULT NULL,
                message LONGTEXT DEFAULT NULL,
                closing_message LONGTEXT DEFAULT NULL,
                special_date DATE DEFAULT NULL,
                color_primary CHAR(7) DEFAULT NULL,
                music_source VARCHAR(20) NOT NULL DEFAULT 'none',
                music_track_id CHAR(36) DEFAULT NULL,
                music_media_id CHAR(36) DEFAULT NULL,
                content_json JSON NOT NULL,
                billing_mode_used VARCHAR(30) DEFAULT NULL,
                views_count INT UNSIGNED NOT NULL DEFAULT 0,
                og_image_url VARCHAR(500) DEFAULT NULL,
                published_at DATETIME(6) DEFAULT NULL,
                deleted_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                updated_at DATETIME(6) NOT NULL,
                UNIQUE INDEX uniq_tributes_slug (slug),
                INDEX idx_tributes_user_status (user_id, status, updated_at),
                PRIMARY KEY (id),
                CONSTRAINT fk_tributes_user FOREIGN KEY (user_id) REFERENCES users (id),
                CONSTRAINT fk_tributes_tribute_type FOREIGN KEY (tribute_type_id) REFERENCES tribute_types (id),
                CONSTRAINT fk_tributes_template FOREIGN KEY (template_id) REFERENCES templates (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE tribute_media (
                id CHAR(36) NOT NULL,
                tribute_id CHAR(36) NOT NULL,
                storage_file_id VARCHAR(64) NOT NULL,
                media_type VARCHAR(20) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) DEFAULT NULL,
                size_bytes INT UNSIGNED DEFAULT NULL,
                sort_order SMALLINT NOT NULL DEFAULT 0,
                url_thumbnail VARCHAR(500) DEFAULT NULL,
                deleted_at DATETIME(6) DEFAULT NULL,
                created_at DATETIME(6) NOT NULL,
                INDEX idx_tribute_media_tribute (tribute_id, sort_order),
                PRIMARY KEY (id),
                CONSTRAINT fk_tribute_media_tribute FOREIGN KEY (tribute_id) REFERENCES tributes (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tribute_media');
        $this->addSql('DROP TABLE tributes');
    }
}
