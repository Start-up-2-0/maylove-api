<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persiste configurações extensíveis e dados específicos dos modelos de álbum';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE albums ADD content_json JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE albums DROP content_json');
    }
}
