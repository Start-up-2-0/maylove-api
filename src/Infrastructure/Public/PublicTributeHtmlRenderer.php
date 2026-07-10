<?php

declare(strict_types=1);

namespace App\Infrastructure\Public;

use App\Infrastructure\Persistence\Entity\Tribute;

final class PublicTributeHtmlRenderer
{
    public function render(Tribute $tribute, string $canonicalUrl, string $frontendUrl, ?string $ogImageUrl): string
    {
        $title = $this->escape($tribute->getTitle() ?: $tribute->getHonoreeName() ?: 'MayLov');
        $description = $this->escape($this->buildDescription($tribute));
        $canonical = $this->escape($canonicalUrl);
        $ogImage = $ogImageUrl !== null && $ogImageUrl !== '' ? $this->escape($ogImageUrl) : '';
        $frontend = $this->escape(rtrim($frontendUrl, '/'));
        $slug = $this->escape($tribute->getSlug());

        $ogImageTags = $ogImage !== ''
            ? "<meta property=\"og:image\" content=\"{$ogImage}\">\n    <meta name=\"twitter:image\" content=\"{$ogImage}\">"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} | MayLov</title>
    <meta name="description" content="{$description}">
    <link rel="canonical" href="{$canonical}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{$title}">
    <meta property="og:description" content="{$description}">
    <meta property="og:url" content="{$canonical}">
    {$ogImageTags}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$title}">
    <meta name="twitter:description" content="{$description}">
</head>
<body>
    <div id="app" data-slug="{$slug}">Carregando homenagem...</div>
    <script type="module" src="{$frontend}/@vite/client"></script>
    <script type="module" src="{$frontend}/src/main.ts"></script>
</body>
</html>
HTML;
    }

    private function buildDescription(Tribute $tribute): string
    {
        $message = trim(strip_tags((string) $tribute->getMessage()));
        if ($message === '') {
            return 'Uma homenagem especial feita com MayLov.';
        }

        if (mb_strlen($message) > 160) {
            return mb_substr($message, 0, 157).'...';
        }

        return $message;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
