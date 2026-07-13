<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona apresentação do livro digital e data de memória nas páginas do álbum.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE albums ADD presentation VARCHAR(40) NOT NULL DEFAULT 'family-album' AFTER color_primary");
        $this->addSql('ALTER TABLE album_media ADD memory_date VARCHAR(50) DEFAULT NULL AFTER caption');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album_media DROP memory_date');
        $this->addSql('ALTER TABLE albums DROP presentation');
    }
}
