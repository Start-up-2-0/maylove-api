<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modelo rico do Álbum Digital: categoria, homenageado, capítulos, memórias, documentos e experiências.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE albums ADD category VARCHAR(32) NOT NULL DEFAULT 'custom' AFTER status");
        $this->addSql("ALTER TABLE albums ADD honoree_names VARCHAR(200) DEFAULT NULL AFTER slug");

        $this->addSql(<<<'SQL'
            CREATE TABLE album_chapters (
                id CHAR(36) NOT NULL,
                album_id CHAR(36) NOT NULL,
                title VARCHAR(200) NOT NULL,
                sort_order SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_album_chapters_album (album_id, sort_order),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE album_memories (
                id CHAR(36) NOT NULL,
                chapter_id CHAR(36) NOT NULL,
                album_id CHAR(36) NOT NULL,
                sort_order SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
                title VARCHAR(200) DEFAULT NULL,
                subtitle VARCHAR(200) DEFAULT NULL,
                description LONGTEXT DEFAULT NULL,
                memory_date DATE DEFAULT NULL,
                memory_time TIME DEFAULT NULL,
                location_lat DECIMAL(10,8) DEFAULT NULL,
                location_lng DECIMAL(11,8) DEFAULT NULL,
                location_label VARCHAR(255) DEFAULT NULL,
                climate VARCHAR(32) DEFAULT NULL,
                sentiment VARCHAR(32) DEFAULT NULL,
                tags_json JSON DEFAULT NULL,
                people_json JSON DEFAULT NULL,
                content_json JSON DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_album_memories_chapter (chapter_id, sort_order),
                INDEX idx_album_memories_album (album_id),
                INDEX idx_album_memories_date (memory_date),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE album_documents (
                id CHAR(36) NOT NULL,
                album_id CHAR(36) NOT NULL,
                memory_id CHAR(36) DEFAULT NULL,
                storage_file_id VARCHAR(64) NOT NULL,
                doc_type VARCHAR(32) DEFAULT 'other' NOT NULL,
                label VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_album_documents_album (album_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE album_experiences (
                id CHAR(36) NOT NULL,
                album_id CHAR(36) NOT NULL,
                memory_id CHAR(36) DEFAULT NULL,
                type VARCHAR(32) NOT NULL,
                config_json JSON NOT NULL,
                sort_order SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_album_experiences_album (album_id, sort_order),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql('ALTER TABLE album_chapters ADD CONSTRAINT FK_album_chapters_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_memories ADD CONSTRAINT FK_album_memories_chapter FOREIGN KEY (chapter_id) REFERENCES album_chapters (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_memories ADD CONSTRAINT FK_album_memories_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_documents ADD CONSTRAINT FK_album_documents_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_documents ADD CONSTRAINT FK_album_documents_memory FOREIGN KEY (memory_id) REFERENCES album_memories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_experiences ADD CONSTRAINT FK_album_experiences_album FOREIGN KEY (album_id) REFERENCES albums (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE album_experiences ADD CONSTRAINT FK_album_experiences_memory FOREIGN KEY (memory_id) REFERENCES album_memories (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album_experiences DROP FOREIGN KEY FK_album_experiences_memory');
        $this->addSql('ALTER TABLE album_experiences DROP FOREIGN KEY FK_album_experiences_album');
        $this->addSql('ALTER TABLE album_documents DROP FOREIGN KEY FK_album_documents_memory');
        $this->addSql('ALTER TABLE album_documents DROP FOREIGN KEY FK_album_documents_album');
        $this->addSql('ALTER TABLE album_memories DROP FOREIGN KEY FK_album_memories_album');
        $this->addSql('ALTER TABLE album_memories DROP FOREIGN KEY FK_album_memories_chapter');
        $this->addSql('ALTER TABLE album_chapters DROP FOREIGN KEY FK_album_chapters_album');
        $this->addSql('DROP TABLE album_experiences');
        $this->addSql('DROP TABLE album_documents');
        $this->addSql('DROP TABLE album_memories');
        $this->addSql('DROP TABLE album_chapters');
        $this->addSql('ALTER TABLE albums DROP honoree_names');
        $this->addSql("ALTER TABLE albums DROP category");
    }
}
