<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria catálogo de tipos de homenagem e templates com seeds do MVP.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE tribute_types (
                id CHAR(36) NOT NULL,
                slug VARCHAR(80) NOT NULL,
                name VARCHAR(120) NOT NULL,
                icon VARCHAR(16) NOT NULL,
                default_palette JSON NOT NULL,
                default_texts JSON NOT NULL,
                sort_order SMALLINT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                UNIQUE INDEX uniq_tribute_types_slug (slug),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE template_categories (
                id CHAR(36) NOT NULL,
                name VARCHAR(80) NOT NULL,
                slug VARCHAR(80) NOT NULL,
                sort_order SMALLINT NOT NULL DEFAULT 0,
                UNIQUE INDEX uniq_template_categories_slug (slug),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE templates (
                id CHAR(36) NOT NULL,
                category_id CHAR(36) NOT NULL,
                slug VARCHAR(80) NOT NULL,
                name VARCHAR(120) NOT NULL,
                thumbnail_url VARCHAR(500) DEFAULT NULL,
                primary_color CHAR(7) NOT NULL,
                max_photos SMALLINT NOT NULL DEFAULT 50,
                supports_video TINYINT(1) NOT NULL DEFAULT 1,
                supports_music TINYINT(1) NOT NULL DEFAULT 1,
                has_animation TINYINT(1) NOT NULL DEFAULT 0,
                config_json JSON NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                UNIQUE INDEX uniq_templates_slug (slug),
                INDEX idx_templates_category (category_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_templates_category FOREIGN KEY (category_id) REFERENCES template_categories (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE template_tribute_types (
                id CHAR(36) NOT NULL,
                template_id CHAR(36) NOT NULL,
                tribute_type_id CHAR(36) NOT NULL,
                UNIQUE INDEX uniq_template_tribute_type (template_id, tribute_type_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_template_tribute_types_template FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE,
                CONSTRAINT fk_template_tribute_types_tribute_type FOREIGN KEY (tribute_type_id) REFERENCES tribute_types (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->seedCatalog();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE template_tribute_types');
        $this->addSql('DROP TABLE templates');
        $this->addSql('DROP TABLE template_categories');
        $this->addSql('DROP TABLE tribute_types');
    }

    private function seedCatalog(): void
    {
        $types = [
            ['11111111-1111-1111-1111-111111111101', 'pedido-namoro', 'Pedido de namoro', '💕', '{"primary":"#E91E63","secondary":"#F8BBD0","background":"#FFF5F8"}', '{"title_suggestion":"Quer namorar comigo?","subtitle_suggestion":"Tenho algo especial para te dizer..."}', 1],
            ['11111111-1111-1111-1111-111111111102', 'pedido-casamento', 'Pedido de casamento', '💍', '{"primary":"#9C27B0","secondary":"#E1BEE7","background":"#FAF5FC"}', '{"title_suggestion":"Casa comigo?","subtitle_suggestion":"Você é o amor da minha vida"}', 2],
            ['11111111-1111-1111-1111-111111111103', 'aniversario', 'Aniversário', '🎂', '{"primary":"#FF9800","secondary":"#FFE0B2","background":"#FFFBF5"}', '{"title_suggestion":"Feliz aniversário!","subtitle_suggestion":"Celebrando você hoje"}', 3],
            ['11111111-1111-1111-1111-111111111104', 'parabens', 'Parabéns', '🎉', '{"primary":"#2196F3","secondary":"#BBDEFB","background":"#F5FAFF"}', '{"title_suggestion":"Parabéns!","subtitle_suggestion":"Por essa conquista incrível"}', 4],
            ['11111111-1111-1111-1111-111111111105', 'pedido-desculpas', 'Pedido de desculpas', '🕊️', '{"primary":"#607D8B","secondary":"#CFD8DC","background":"#F8FAFB"}', '{"title_suggestion":"Me perdoa?","subtitle_suggestion":"Você significa muito para mim"}', 5],
            ['11111111-1111-1111-1111-111111111106', 'declaracao-amor', 'Declaração de amor', '❤️', '{"primary":"#D94F7A","secondary":"#F4C2D0","background":"#FFF8FA"}', '{"title_suggestion":"Eu te amo","subtitle_suggestion":"Cada momento ao seu lado é especial"}', 6],
        ];

        foreach ($types as [$id, $slug, $name, $icon, $palette, $texts, $sort]) {
            $this->addSql(
                'INSERT INTO tribute_types (id, slug, name, icon, default_palette, default_texts, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
                [$id, $slug, $name, $icon, $palette, $texts, $sort],
            );
        }

        $categories = [
            ['22222222-2222-2222-2222-222222222201', 'Clássico', 'classico', 1],
            ['22222222-2222-2222-2222-222222222202', 'Moderno', 'moderno', 2],
            ['22222222-2222-2222-2222-222222222203', 'Elegante', 'elegante', 3],
            ['22222222-2222-2222-2222-222222222204', 'Festivo', 'festivo', 4],
            ['22222222-2222-2222-2222-222222222205', 'Íntimo', 'intimo', 5],
        ];

        foreach ($categories as [$id, $name, $slug, $sort]) {
            $this->addSql(
                'INSERT INTO template_categories (id, name, slug, sort_order) VALUES (?, ?, ?, ?)',
                [$id, $name, $slug, $sort],
            );
        }

        $templates = [
            ['33333333-3333-3333-3333-333333333301', '22222222-2222-2222-2222-222222222201', 'romantico-classico', 'Romântico Clássico', '/platform/templates/romantico-classico.jpg', '#D94F7A', 50, 1, 1, 1, '{"fonts":{"heading":"Playfair Display","body":"Inter"},"animations":{"entrance":"fade-in","photos":"ken-burns"},"timings":{"photo_interval_ms":4500}}'],
            ['33333333-3333-3333-3333-333333333302', '22222222-2222-2222-2222-222222222202', 'moderno-minimal', 'Moderno Minimal', '/platform/templates/moderno-minimal.jpg', '#212121', 50, 1, 1, 0, '{"fonts":{"heading":"Montserrat","body":"Open Sans"},"animations":{"entrance":"slide-up"},"timings":{"photo_interval_ms":3500}}'],
            ['33333333-3333-3333-3333-333333333303', '22222222-2222-2222-2222-222222222203', 'elegante-dourado', 'Elegante Dourado', '/platform/templates/elegante-dourado.jpg', '#B8860B', 50, 1, 1, 1, '{"fonts":{"heading":"Cormorant Garamond","body":"Lato"},"animations":{"entrance":"fade-in","sparkle":true},"timings":{"photo_interval_ms":5000}}'],
            ['33333333-3333-3333-3333-333333333304', '22222222-2222-2222-2222-222222222204', 'animado-festivo', 'Animado Festivo', '/platform/templates/animado-festivo.jpg', '#FF5722', 50, 1, 1, 1, '{"fonts":{"heading":"Poppins","body":"Nunito"},"animations":{"entrance":"bounce","confetti":true},"timings":{"photo_interval_ms":3000}}'],
            ['33333333-3333-3333-3333-333333333305', '22222222-2222-2222-2222-222222222205', 'intimo-suave', 'Íntimo Suave', '/platform/templates/intimo-suave.jpg', '#8E6C88', 50, 0, 1, 0, '{"fonts":{"heading":"Merriweather","body":"Source Sans 3"},"animations":{"entrance":"gentle-fade"},"timings":{"photo_interval_ms":6000}}'],
        ];

        foreach ($templates as [$id, $categoryId, $slug, $name, $thumb, $color, $maxPhotos, $video, $music, $animation, $config]) {
            $this->addSql(
                'INSERT INTO templates (id, category_id, slug, name, thumbnail_url, primary_color, max_photos, supports_video, supports_music, has_animation, config_json, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
                [$id, $categoryId, $slug, $name, $thumb, $color, $maxPhotos, $video, $music, $animation, $config],
            );
        }

        $linkId = 1;
        foreach (['33333333-3333-3333-3333-333333333301', '33333333-3333-3333-3333-333333333302', '33333333-3333-3333-3333-333333333303', '33333333-3333-3333-3333-333333333304', '33333333-3333-3333-3333-333333333305'] as $templateId) {
            foreach (['11111111-1111-1111-1111-111111111101', '11111111-1111-1111-1111-111111111102', '11111111-1111-1111-1111-111111111103', '11111111-1111-1111-1111-111111111104', '11111111-1111-1111-1111-111111111105', '11111111-1111-1111-1111-111111111106'] as $typeId) {
                $uuid = sprintf('44444444-4444-4444-4444-%012d', $linkId++);
                $this->addSql(
                    'INSERT INTO template_tribute_types (id, template_id, tribute_type_id) VALUES (?, ?, ?)',
                    [$uuid, $templateId, $typeId],
                );
            }
        }
    }
}
