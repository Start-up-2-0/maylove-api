<?php

declare(strict_types=1);

namespace App\Application\Wizard;

/**
 * Fonte da verdade do "schema" que dirige o wizard de criação de homenagens.
 *
 * Cada estilo de apresentação (a "experiência") tem uma estrutura própria:
 * quais passos existem, em que ordem e quais limites/validações se aplicam.
 * O frontend interpreta essa configuração para montar dinamicamente o
 * formulário e o preview, sem regras fixas por apresentação.
 *
 * IMPORTANTE: manter em paridade com o espelho do frontend em
 * `maylove-app/src/templates/presentationSchema.ts`. Passos dependentes do
 * template (vídeo por capacidade, evento por categoria) são acrescentados no
 * frontend, pois dependem do template escolhido, não da apresentação.
 */
final class PresentationSchemaProvider
{
    private const STEP_LABELS = [
        'presentation' => 'Apresentação',
        'style' => 'Estilo',
        'texts' => 'Textos',
        'moments' => 'Momentos',
        'photos' => 'Fotos',
        'music' => 'Música',
        'video' => 'Vídeo',
        'event' => 'Evento',
        'effects' => 'Efeitos',
        'preview' => 'Revisar e Concluir',
        'publish' => 'Publicar',
    ];

    /** Layouts narrativos/foto-centrados usam o editor de "Momentos". */
    private const MOMENT_LAYOUTS = ['timeline', 'album', 'storytelling', 'cinematic', 'proposal'];

    /** Mínimo de fotos recomendado por layout (0 = opcional). */
    private const MIN_PHOTOS = [
        'scroll' => 1,
        'letter' => 0,
        'envelope' => 0,
        'cinematic' => 3,
        'album' => 2,
        'storytelling' => 2,
        'timeline' => 1,
        'proposal' => 1,
    ];

    /**
     * @return list<array{
     *   id: string, label: string, emoji: string, description: string,
     *   layout: string, highlights: list<string>,
     *   config: array{music: bool, minimal: bool, dramatic: bool},
     *   steps: list<array{id: string, label: string}>,
     *   limits: array{minPhotos: int, maxPhotos: int|null},
     *   required: list<string>
     * }>
     */
    public function list(): array
    {
        return array_map(
            fn (array $p): array => $this->buildEntry($p),
            $this->presentations(),
        );
    }

    /**
     * Requisitos de mídia para validação, resolvidos pela apresentação escolhida.
     * Se a apresentação não for encontrada, assume o padrão (1 foto).
     *
     * @return array{minPhotos: int, usesMoments: bool}
     */
    public function requirementsFor(?string $presentationId): array
    {
        foreach ($this->presentations() as $p) {
            if (($p['id'] ?? null) === $presentationId) {
                $layout = (string) $p['layout'];

                return [
                    'minPhotos' => self::MIN_PHOTOS[$layout] ?? 1,
                    'usesMoments' => in_array($layout, self::MOMENT_LAYOUTS, true),
                ];
            }
        }

        return ['minPhotos' => 1, 'usesMoments' => false];
    }

    /**
     * @param array<string, mixed> $p
     *
     * @return array<string, mixed>
     */
    private function buildEntry(array $p): array
    {
        $layout = (string) $p['layout'];
        $config = [
            'music' => (bool) ($p['music'] ?? true),
            'minimal' => (bool) ($p['minimal'] ?? false),
            'dramatic' => (bool) ($p['dramatic'] ?? false),
        ];

        return [
            'id' => (string) $p['id'],
            'label' => (string) $p['label'],
            'emoji' => (string) $p['emoji'],
            'description' => (string) $p['description'],
            'layout' => $layout,
            'highlights' => $p['highlights'],
            'config' => $config,
            'steps' => $this->stepsFor($layout, $config),
            'limits' => [
                'minPhotos' => self::MIN_PHOTOS[$layout] ?? 1,
                'maxPhotos' => null,
            ],
            'required' => ['title', 'message'],
        ];
    }

    /**
     * @param array{music: bool, minimal: bool, dramatic: bool} $config
     *
     * @return list<array{id: string, label: string}>
     */
    private function stepsFor(string $layout, array $config): array
    {
        $ids = ['presentation', 'style', 'texts', 'photos'];
        if (in_array($layout, self::MOMENT_LAYOUTS, true)) {
            $ids[] = 'moments';
        }
        if ($config['music'] !== false) {
            $ids[] = 'music';
        }
        if ($config['minimal'] !== true) {
            $ids[] = 'effects';
        }
        $ids[] = 'preview';
        $ids[] = 'publish';

        return array_map(
            fn (string $id): array => ['id' => $id, 'label' => self::STEP_LABELS[$id] ?? $id],
            $ids,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function presentations(): array
    {
        return [
            [
                'id' => 'carta-simples', 'label' => 'Carta Simples', 'emoji' => '💌',
                'description' => 'Experiência minimalista, focada na mensagem escrita.',
                'layout' => 'letter', 'music' => false, 'minimal' => true,
                'highlights' => ['Carta elegante e centrada', 'Fotos opcionais', 'Sem animações complexas'],
            ],
            [
                'id' => 'carta-animada', 'label' => 'Carta Animada', 'emoji' => '✉️',
                'description' => 'Uma carta que conta uma história, com envelope e digitação.',
                'layout' => 'envelope', 'music' => true,
                'highlights' => ['Envelope que abre', 'Texto surgindo aos poucos', 'Fotos entre os parágrafos'],
            ],
            [
                'id' => 'slider-fotos', 'label' => 'Slider de Fotos', 'emoji' => '🎞️',
                'description' => 'Apresentação em tela cheia com fotos em destaque.',
                'layout' => 'cinematic', 'music' => false,
                'highlights' => ['Fotos em tela cheia', 'Legendas personalizadas', 'Efeito Ken Burns'],
            ],
            [
                'id' => 'slider-musica', 'label' => 'Slider com Música', 'emoji' => '🎵',
                'description' => 'Como um videoclipe: fotos sincronizadas com trilha sonora.',
                'layout' => 'cinematic', 'music' => true,
                'highlights' => ['Player integrado', 'Fotos sincronizadas', 'Transições cinematográficas'],
            ],
            [
                'id' => 'storytelling', 'label' => 'Storytelling', 'emoji' => '📖',
                'description' => 'Narrativa interativa em capítulos, como um curta.',
                'layout' => 'storytelling', 'music' => true,
                'highlights' => ['Seções em tela cheia', 'Fotos, textos e vídeo', 'Animações entre capítulos'],
            ],
            [
                'id' => 'timeline', 'label' => 'Timeline', 'emoji' => '🕰️',
                'description' => 'Linha do tempo para contar uma trajetória.',
                'layout' => 'timeline', 'music' => false,
                'highlights' => ['Datas importantes', 'Fotos e mensagens', 'Animações ao rolar'],
            ],
            [
                'id' => 'album', 'label' => 'Álbum de Memórias', 'emoji' => '📚',
                'description' => 'Fotos organizadas como um álbum que se folheia.',
                'layout' => 'album', 'music' => true,
                'highlights' => ['Efeito de virar páginas', 'Mensagem em cada página', 'Música ambiente'],
            ],
            [
                'id' => 'cinematografico', 'label' => 'Cinematográfico', 'emoji' => '🎬',
                'description' => 'Experiência tipo trailer, com clima de grande tela.',
                'layout' => 'cinematic', 'music' => true, 'dramatic' => true,
                'highlights' => ['Abertura em tela cheia', 'Textos em momentos-chave', 'Transições suaves'],
            ],
            [
                'id' => 'rolagem', 'label' => 'Rolagem Clássica', 'emoji' => '📜',
                'description' => 'Seções empilhadas, exploradas pela rolagem da página.',
                'layout' => 'scroll', 'music' => true,
                'highlights' => ['Blocos reordenáveis', 'Galeria e mensagens', 'Fluxo tradicional'],
            ],
            [
                'id' => 'pedido', 'label' => 'Pedido Interativo', 'emoji' => '💍',
                'description' => 'História em etapas terminando em uma grande pergunta.',
                'layout' => 'proposal', 'music' => true,
                'highlights' => ['História em passos', 'Botão “Você aceita?”', 'Final surpreendente'],
            ],
        ];
    }
}
