<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add place_name to album_media for photobook location captions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album_media ADD place_name VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE album_media DROP place_name');
    }
}
