<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona book_config e book_pages (JSON) para Memory Book personalizável.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE albums ADD book_config JSON DEFAULT NULL AFTER photos_per_page');
        $this->addSql('ALTER TABLE albums ADD book_pages JSON DEFAULT NULL AFTER book_config');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE albums DROP book_pages');
        $this->addSql('ALTER TABLE albums DROP book_config');
    }
}
