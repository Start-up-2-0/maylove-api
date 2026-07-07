<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707240000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria biblioteca musical (music_tracks) com 15 faixas seed e FK em tributes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE music_tracks (
                id CHAR(36) NOT NULL,
                slug VARCHAR(80) NOT NULL,
                title VARCHAR(160) NOT NULL,
                artist VARCHAR(120) NOT NULL,
                duration_seconds SMALLINT NOT NULL,
                relative_path VARCHAR(255) NOT NULL,
                preview_url VARCHAR(500) DEFAULT NULL,
                category VARCHAR(50) NOT NULL,
                sort_order SMALLINT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                UNIQUE INDEX uniq_music_tracks_slug (slug),
                INDEX idx_music_tracks_category (category, sort_order),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE tributes
                ADD CONSTRAINT fk_tributes_music_track
                FOREIGN KEY (music_track_id) REFERENCES music_tracks (id) ON DELETE SET NULL
        SQL);

        $this->seedTracks();
    }

    private function seedTracks(): void
    {
        $tracks = [
            ['01950000-0000-7000-8000-000000000001', 'acorde-do-coracao', 'Acorde do Coração', 'MayLove Studio', 186, 'platform/music/acorde-do-coracao.mp3', 'romantico', 1],
            ['01950000-0000-7000-8000-000000000002', 'eterno-amor', 'Eterno Amor', 'Piano Suave', 204, 'platform/music/eterno-amor.mp3', 'romantico', 2],
            ['01950000-0000-7000-8000-000000000003', 'sussurro-de-amor', 'Sussurro de Amor', 'Cordas Delicadas', 192, 'platform/music/sussurro-de-amor.mp3', 'romantico', 3],
            ['01950000-0000-7000-8000-000000000004', 'festa-do-coracao', 'Festa do Coração', 'Brass Light', 178, 'platform/music/festa-do-coracao.mp3', 'celebracao', 4],
            ['01950000-0000-7000-8000-000000000005', 'momento-especial', 'Momento Especial', 'Pop Acústico', 210, 'platform/music/momento-especial.mp3', 'celebracao', 5],
            ['01950000-0000-7000-8000-000000000006', 'brinde-a-vida', 'Brinde à Vida', 'Indie Joy', 195, 'platform/music/brinde-a-vida.mp3', 'celebracao', 6],
            ['01950000-0000-7000-8000-000000000007', 'serenidade', 'Serenidade', 'Ambient Calm', 240, 'platform/music/serenidade.mp3', 'suave', 7],
            ['01950000-0000-7000-8000-000000000008', 'luz-da-manha', 'Luz da Manhã', 'Guitar Fingerstyle', 188, 'platform/music/luz-da-manha.mp3', 'suave', 8],
            ['01950000-0000-7000-8000-000000000009', 'paz-interior', 'Paz Interior', 'Pad Etéreo', 225, 'platform/music/paz-interior.mp3', 'suave', 9],
            ['01950000-0000-7000-8000-000000000010', 'memorias', 'Memórias', 'Piano Nostálgico', 216, 'platform/music/memorias.mp3', 'nostalgico', 10],
            ['01950000-0000-7000-8000-000000000011', 'tempo-bom', 'Tempo Bom', 'Vintage Waltz', 198, 'platform/music/tempo-bom.mp3', 'nostalgico', 11],
            ['01950000-0000-7000-8000-000000000012', 'saudade-boa', 'Saudade Boa', 'Violão Acústico', 207, 'platform/music/saudade-boa.mp3', 'nostalgico', 12],
            ['01950000-0000-7000-8000-000000000013', 'alegria-pura', 'Alegria Pura', 'Ukulele', 172, 'platform/music/alegria-pura.mp3', 'alegre', 13],
            ['01950000-0000-7000-8000-000000000014', 'sol-de-verao', 'Sol de Verão', 'Tropical Beat', 184, 'platform/music/sol-de-verao.mp3', 'alegre', 14],
            ['01950000-0000-7000-8000-000000000015', 'vibe-feliz', 'Vibe Feliz', 'Funk Light', 190, 'platform/music/vibe-feliz.mp3', 'alegre', 15],
        ];

        foreach ($tracks as [$id, $slug, $title, $artist, $duration, $path, $category, $sort]) {
            $this->addSql(
                'INSERT INTO music_tracks (id, slug, title, artist, duration_seconds, relative_path, preview_url, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, 1)',
                [$id, $slug, $title, $artist, $duration, $path, $category, $sort],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tributes DROP FOREIGN KEY fk_tributes_music_track');
        $this->addSql('DROP TABLE music_tracks');
    }
}
