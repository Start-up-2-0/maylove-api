<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona contador de compartilhamentos iniciados às homenagens';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tributes ADD shares_count INT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tributes DROP shares_count');
    }
}
