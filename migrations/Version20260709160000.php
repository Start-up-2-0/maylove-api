<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona status de processamento em tribute_media para extração de áudio.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE tribute_media
                ADD processing_status VARCHAR(20) NOT NULL DEFAULT 'ready',
                ADD processing_error VARCHAR(500) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tribute_media DROP processing_status, DROP processing_error');
    }
}
