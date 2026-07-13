<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona fotos por página na configuração do álbum.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE albums ADD photos_per_page SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER presentation');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE albums DROP photos_per_page');
    }
}
