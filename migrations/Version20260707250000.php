<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Semeia o catálogo real (categorias por ocasião, tipos e templates) alinhado ao
 * registry do frontend. Os SLUGS dos templates precisam coincidir com os das
 * definições em maylove-app/src/templates para que a experiência seja resolvida
 * corretamente e o fluxo de criação funcione ponta a ponta.
 *
 * Usa INSERT IGNORE + INSERT ... SELECT (resolvendo ids por slug) para ser
 * robusto a linhas já existentes e a reexecuções parciais.
 */
final class Version20260707250000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Semeia categorias por ocasião, tipos e templates alinhados ao registry do frontend.';
    }

    /** Gera um UUID determinístico a partir de uma semente (estável entre execuções). */
    private function uuidFor(string $seed): string
    {
        $h = md5($seed);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($h, 0, 8),
            substr($h, 8, 4),
            substr($h, 12, 4),
            substr($h, 16, 4),
            substr($h, 20, 12),
        );
    }

    public function up(Schema $schema): void
    {
        // Categorias por ocasião (slug, label, ordem).
        $categories = [
            ['amor', 'Declaração de Amor', 1],
            ['pedido-namoro', 'Pedido de Namoro', 2],
            ['pedido-casamento', 'Pedido de Casamento', 3],
            ['casamento', 'Casamento', 4],
            ['aniversario', 'Aniversário', 5],
            ['dia-das-maes', 'Dia das Mães', 6],
            ['dia-dos-pais', 'Dia dos Pais', 7],
            ['cha-de-bebe', 'Chá de Bebê', 8],
            ['formatura', 'Formatura', 9],
            ['natal', 'Natal', 10],
            ['ano-novo', 'Ano Novo', 11],
            ['memorial', 'Memorial', 12],
            ['agradecimento', 'Agradecimento', 13],
            ['datas', 'Datas Comemorativas', 14],
            ['outras', 'Outras Homenagens', 15],
        ];

        foreach ($categories as [$slug, $name, $sort]) {
            $this->addSql(
                'INSERT IGNORE INTO template_categories (id, name, slug, sort_order) VALUES (?, ?, ?, ?)',
                [$this->uuidFor('cat:'.$slug), $name, $slug, $sort],
            );
        }

        // Tipos de homenagem usados pelas definições (slug, nome, ícone, ordem).
        $palette = '{"primary":"#D94F7A","secondary":"#F4C2D0","background":"#FFF8FA"}';
        $texts = '{"title_suggestion":"Uma homenagem especial","subtitle_suggestion":"Com todo o carinho"}';

        $types = [
            ['namorados', 'Namorados', '🥰', 20],
            ['pedido', 'Pedido de Casamento', '💐', 21],
            ['casamento', 'Casamento', '💍', 22],
            ['aniversario', 'Aniversário', '🎂', 23],
            ['dia-das-maes', 'Dia das Mães', '💐', 24],
            ['dia-dos-pais', 'Dia dos Pais', '👨', 25],
            ['cha-de-bebe', 'Chá de Bebê', '👶', 26],
            ['formatura', 'Formatura', '🎓', 27],
            ['natal', 'Natal', '🎄', 28],
            ['ano-novo', 'Ano Novo', '🎆', 29],
            ['dia-da-mulher', 'Dia da Mulher', '🌸', 30],
            ['dia-dos-professores', 'Dia dos Professores', '👩‍🏫', 31],
            ['memorial', 'Memorial', '🕊️', 32],
            ['carta', 'Carta Digital', '💌', 33],
            ['historia', 'História de Vida', '📖', 34],
            ['homenagem', 'Homenagem', '💗', 35],
        ];

        foreach ($types as [$slug, $name, $icon, $sort]) {
            $this->addSql(
                'INSERT IGNORE INTO tribute_types (id, slug, name, icon, default_palette, default_texts, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)',
                [$this->uuidFor('type:'.$slug), $slug, $name, $icon, $palette, $texts, $sort],
            );
        }

        // Templates: [slug, nome, categoria, tipo, cor, maxFotos, vídeo, música, animação].
        $config = '{"experience":true}';
        $templates = [
            // Base
            ['classic', 'Clássico', 'outras', 'homenagem', '#d94f7a', 20, 1, 1, 1],
            ['namorados', 'Dia dos Namorados', 'amor', 'namorados', '#e0245e', 20, 1, 1, 1],
            ['aniversario', 'Feliz Aniversário', 'aniversario', 'aniversario', '#f59e0b', 24, 1, 1, 1],
            ['carta-digital', 'Carta Digital Animada', 'agradecimento', 'carta', '#b4763f', 8, 0, 1, 1],
            ['memorial', 'Memorial', 'memorial', 'memorial', '#c9a86a', 30, 1, 1, 1],
            // Ocasiões
            ['dia-das-maes', 'Dia das Mães', 'dia-das-maes', 'dia-das-maes', '#ec4899', 20, 1, 1, 1],
            ['dia-dos-pais', 'Dia dos Pais', 'dia-dos-pais', 'dia-dos-pais', '#0f766e', 24, 1, 1, 1],
            ['casamento', 'Casamento', 'casamento', 'casamento', '#b08968', 24, 1, 1, 1],
            ['cha-de-bebe', 'Chá de Bebê', 'cha-de-bebe', 'cha-de-bebe', '#60a5fa', 20, 1, 1, 1],
            ['pedido-casamento', 'Pedido de Casamento', 'pedido-casamento', 'pedido', '#be123c', 16, 1, 1, 1],
            ['pedido-namoro', 'Pedido de Namoro', 'pedido-namoro', 'pedido', '#e11d48', 12, 0, 1, 1],
            ['formatura', 'Formatura', 'formatura', 'formatura', '#1d4ed8', 24, 1, 1, 1],
            ['natal', 'Natal', 'natal', 'natal', '#c1121f', 20, 1, 1, 1],
            ['ano-novo', 'Ano Novo', 'ano-novo', 'ano-novo', '#eab308', 20, 1, 1, 1],
            ['dia-da-mulher', 'Dia da Mulher', 'datas', 'dia-da-mulher', '#9333ea', 20, 1, 1, 1],
            ['dia-dos-professores', 'Dia dos Professores', 'agradecimento', 'dia-dos-professores', '#0891b2', 20, 1, 1, 1],
            ['historia', 'História da Nossa Vida', 'outras', 'historia', '#a16207', 24, 1, 1, 1],
            // Coleções (2ª variação por categoria)
            ['amor-carta', 'Declaração em Carta', 'amor', 'namorados', '#be123c', 6, 0, 1, 1],
            ['pedido-namoro-carta', 'Convite em Carta', 'pedido-namoro', 'pedido', '#db2777', 6, 0, 1, 1],
            ['pedido-casamento-cinema', 'Pedido Cinematográfico', 'pedido-casamento', 'pedido', '#9f1239', 16, 1, 1, 1],
            ['casamento-album', 'Álbum dos Noivos', 'casamento', 'casamento', '#a16207', 24, 1, 1, 1],
            ['aniversario-scroll', 'Aniversário Clássico', 'aniversario', 'aniversario', '#f97316', 24, 1, 1, 1],
            ['dia-das-maes-carta', 'Carta para Mãe', 'dia-das-maes', 'dia-das-maes', '#db2777', 6, 0, 1, 1],
            ['dia-dos-pais-cinema', 'Homenagem ao Pai', 'dia-dos-pais', 'dia-dos-pais', '#0f766e', 20, 1, 1, 1],
            ['cha-de-bebe-scroll', 'Chá de Bebê Festivo', 'cha-de-bebe', 'cha-de-bebe', '#38bdf8', 16, 1, 1, 1],
            ['formatura-cinema', 'Formatura Cinematográfica', 'formatura', 'formatura', '#1d4ed8', 24, 1, 1, 1],
            ['natal-scroll', 'Natal Aconchegante', 'natal', 'natal', '#b91c1c', 16, 1, 1, 1],
            ['ano-novo-scroll', 'Retrospectiva do Ano', 'ano-novo', 'ano-novo', '#eab308', 20, 1, 1, 1],
            ['memorial-carta', 'Memorial em Carta', 'memorial', 'memorial', '#a8763e', 6, 0, 1, 1],
            ['datas-scroll', 'Data Comemorativa', 'datas', 'dia-da-mulher', '#9333ea', 16, 1, 1, 1],
        ];

        foreach ($templates as [$slug, $name, $catSlug, $typeSlug, $color, $maxPhotos, $video, $music, $animation]) {
            $this->addSql(
                'INSERT IGNORE INTO templates (id, category_id, slug, name, thumbnail_url, primary_color, max_photos, supports_video, supports_music, has_animation, config_json, is_active) '
                .'SELECT ?, tc.id, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1 FROM template_categories tc WHERE tc.slug = ?',
                [$this->uuidFor('tpl:'.$slug), $slug, $name, null, $color, $maxPhotos, $video, $music, $animation, $config, $catSlug],
            );

            // Vincula o template ao seu tipo de homenagem.
            $this->addSql(
                'INSERT IGNORE INTO template_tribute_types (id, template_id, tribute_type_id) '
                .'SELECT ?, t.id, tt.id FROM templates t, tribute_types tt WHERE t.slug = ? AND tt.slug = ?',
                [$this->uuidFor('link:'.$slug.':'.$typeSlug), $slug, $typeSlug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $templateSlugs = [
            'classic', 'namorados', 'aniversario', 'carta-digital', 'memorial',
            'dia-das-maes', 'dia-dos-pais', 'casamento', 'cha-de-bebe', 'pedido-casamento',
            'pedido-namoro', 'formatura', 'natal', 'ano-novo', 'dia-da-mulher',
            'dia-dos-professores', 'historia',
            'amor-carta', 'pedido-namoro-carta', 'pedido-casamento-cinema', 'casamento-album',
            'aniversario-scroll', 'dia-das-maes-carta', 'dia-dos-pais-cinema', 'cha-de-bebe-scroll',
            'formatura-cinema', 'natal-scroll', 'ano-novo-scroll', 'memorial-carta', 'datas-scroll',
        ];
        $placeholders = implode(', ', array_fill(0, count($templateSlugs), '?'));

        // Remove vínculos e templates semeados aqui (links não caem em cascata por slug).
        $this->addSql(
            "DELETE FROM template_tribute_types WHERE template_id IN (SELECT id FROM templates WHERE slug IN ($placeholders))",
            $templateSlugs,
        );
        $this->addSql(
            "DELETE FROM templates WHERE slug IN ($placeholders)",
            $templateSlugs,
        );

        // Tipos exclusivos desta migração (não remove 'aniversario', criado antes).
        $typeSlugs = [
            'namorados', 'pedido', 'casamento', 'dia-das-maes', 'dia-dos-pais', 'cha-de-bebe',
            'formatura', 'natal', 'ano-novo', 'dia-da-mulher', 'dia-dos-professores', 'memorial',
            'carta', 'historia', 'homenagem',
        ];
        $typePlaceholders = implode(', ', array_fill(0, count($typeSlugs), '?'));
        $this->addSql(
            "DELETE FROM tribute_types WHERE slug IN ($typePlaceholders)",
            $typeSlugs,
        );

        $categorySlugs = [
            'amor', 'pedido-namoro', 'pedido-casamento', 'casamento', 'aniversario', 'dia-das-maes',
            'dia-dos-pais', 'cha-de-bebe', 'formatura', 'natal', 'ano-novo', 'memorial',
            'agradecimento', 'datas', 'outras',
        ];
        $catPlaceholders = implode(', ', array_fill(0, count($categorySlugs), '?'));
        $this->addSql(
            "DELETE FROM template_categories WHERE slug IN ($catPlaceholders)",
            $categorySlugs,
        );
    }
}
