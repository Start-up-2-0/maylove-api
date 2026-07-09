<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Application\Billing\PendingOrderInvalidationService;
use App\Domain\Tribute\Exception\TributeException;
use App\Domain\Tribute\MusicSource;
use App\Domain\Tribute\TributeEffects;
use App\Domain\Tribute\TributeStatus;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\Catalog\TemplateRepository;
use App\Infrastructure\Persistence\Repository\Catalog\TributeTypeRepository;
use App\Infrastructure\Persistence\Repository\Music\MusicTrackRepository;
use App\Infrastructure\Persistence\Repository\TributeRepository;
use Symfony\Component\Uid\Uuid;

final class TributeService
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
        private readonly TributeTypeRepository $tributeTypeRepository,
        private readonly TemplateRepository $templateRepository,
        private readonly MusicTrackRepository $musicTrackRepository,
        private readonly TributeSlugGenerator $slugGenerator,
        private readonly PendingOrderInvalidationService $pendingOrderInvalidation,
    ) {
    }

    public function create(User $user, string $tributeTypeId, string $templateId): Tribute
    {
        if (!$user->isEmailVerified()) {
            throw new TributeException(
                'Confirme seu e-mail antes de criar uma homenagem.',
                'EMAIL_NOT_VERIFIED',
                403,
            );
        }

        if (!Uuid::isValid($tributeTypeId) || !Uuid::isValid($templateId)) {
            throw new TributeException('tribute_type_id e template_id são obrigatórios.', 'VALIDATION_ERROR', 400);
        }

        $tributeType = $this->tributeTypeRepository->findActiveById(Uuid::fromString($tributeTypeId));
        $template = $this->templateRepository->findActiveById(Uuid::fromString($templateId));

        if ($tributeType === null || $template === null) {
            throw new TributeException('Tipo ou template não encontrado.', 'CATALOG_NOT_FOUND', 404);
        }

        if (!$this->templateRepository->isLinkedToTributeType($template->getId(), $tributeType->getId())) {
            throw new TributeException('Template incompatível com o tipo de homenagem.', 'TEMPLATE_INCOMPATIBLE', 422);
        }

        $defaultTexts = $tributeType->getDefaultTexts();
        $slug = $this->slugGenerator->generateUnique();

        $tribute = new Tribute($user, $tributeType, $template, $slug);
        $tribute->setTitle(isset($defaultTexts['title']) ? (string) $defaultTexts['title'] : null);
        $tribute->setSubtitle(isset($defaultTexts['subtitle']) ? (string) $defaultTexts['subtitle'] : null);
        $tribute->setColorPrimary($template->getPrimaryColor());

        $this->tributeRepository->save($tribute);

        return $tribute;
    }

    /**
     * @return list<Tribute>
     */
    public function listByUser(User $user, ?string $status = null): array
    {
        $statusEnum = null;
        if ($status !== null && $status !== '') {
            $statusEnum = TributeStatus::tryFrom($status);
            if ($statusEnum === null) {
                throw new TributeException('Status inválido.', 'VALIDATION_ERROR', 400);
            }
        }

        return $this->tributeRepository->findByUser($user, $statusEnum);
    }

    public function getOwned(string $id, User $user): Tribute
    {
        if (!Uuid::isValid($id)) {
            throw new TributeException('Homenagem não encontrada.', 'TRIBUTE_NOT_FOUND', 404);
        }

        $tribute = $this->tributeRepository->findOwnedById(Uuid::fromString($id), $user->getId());
        if ($tribute === null) {
            throw new TributeException('Homenagem não encontrada.', 'TRIBUTE_NOT_FOUND', 404);
        }

        return $tribute;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Tribute $tribute, array $payload): Tribute
    {
        $this->assertEditable($tribute);
        $this->pendingOrderInvalidation->invalidateIfAwaitingPayment($tribute);

        if (array_key_exists('title', $payload)) {
            $tribute->setTitle($payload['title'] !== null ? (string) $payload['title'] : null);
        }

        if (array_key_exists('subtitle', $payload)) {
            $tribute->setSubtitle($payload['subtitle'] !== null ? (string) $payload['subtitle'] : null);
        }

        if (array_key_exists('honoree_name', $payload)) {
            $tribute->setHonoreeName($payload['honoree_name'] !== null ? (string) $payload['honoree_name'] : null);
        }

        if (array_key_exists('message', $payload)) {
            $message = $payload['message'] !== null ? strip_tags(trim((string) $payload['message'])) : null;
            if ($message !== null && mb_strlen($message) > 5000) {
                throw new TributeException('Mensagem muito longa (máx. 5000).', 'VALIDATION_ERROR', 400);
            }
            $tribute->setMessage($message);
        }

        if (array_key_exists('closing_message', $payload)) {
            $closing = $payload['closing_message'] !== null ? strip_tags(trim((string) $payload['closing_message'])) : null;
            $tribute->setClosingMessage($closing);
        }

        if (array_key_exists('special_date', $payload)) {
            $tribute->setSpecialDate($this->parseDate($payload['special_date']));
        }

        if (array_key_exists('slug', $payload) && $payload['slug'] !== null) {
            $slug = strtolower(trim((string) $payload['slug']));
            if (!$this->slugGenerator->isValidFormat($slug)) {
                throw new TributeException('Slug inválido.', 'INVALID_SLUG', 400);
            }
            if ($this->tributeRepository->existsBySlug($slug, $tribute->getId())) {
                throw new TributeException('Este slug já está em uso.', 'SLUG_ALREADY_EXISTS', 409);
            }
            $tribute->setSlug($slug);
        }

        if (array_key_exists('color_primary', $payload) && $payload['color_primary'] !== null) {
            $color = (string) $payload['color_primary'];
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                throw new TributeException('Cor primária inválida.', 'VALIDATION_ERROR', 400);
            }
            $tribute->setColorPrimary($color);
        }

        if (array_key_exists('tribute_type_id', $payload) && $payload['tribute_type_id'] !== null) {
            if (!Uuid::isValid((string) $payload['tribute_type_id'])) {
                throw new TributeException('Tipo de homenagem inválido.', 'VALIDATION_ERROR', 400);
            }
            $type = $this->tributeTypeRepository->findActiveById(Uuid::fromString((string) $payload['tribute_type_id']));
            if ($type === null) {
                throw new TributeException('Tipo de homenagem não encontrado.', 'CATALOG_NOT_FOUND', 404);
            }
            if (!$this->templateRepository->isLinkedToTributeType($tribute->getTemplate()->getId(), $type->getId())) {
                throw new TributeException('Template atual incompatível com o novo tipo.', 'TEMPLATE_INCOMPATIBLE', 422);
            }
            $tribute->setTributeType($type);
        }

        if (array_key_exists('template_id', $payload) && $payload['template_id'] !== null) {
            if (!Uuid::isValid((string) $payload['template_id'])) {
                throw new TributeException('Template inválido.', 'VALIDATION_ERROR', 400);
            }
            $template = $this->templateRepository->findActiveById(Uuid::fromString((string) $payload['template_id']));
            if ($template === null) {
                throw new TributeException('Template não encontrado.', 'CATALOG_NOT_FOUND', 404);
            }
            if (!$this->templateRepository->isLinkedToTributeType($template->getId(), $tribute->getTributeType()->getId())) {
                throw new TributeException('Template incompatível com o tipo de homenagem.', 'TEMPLATE_INCOMPATIBLE', 422);
            }
            $tribute->setTemplate($template);
        }

        if (array_key_exists('music_source', $payload) && $payload['music_source'] !== null) {
            $source = MusicSource::tryFrom((string) $payload['music_source']);
            if ($source === null) {
                throw new TributeException('Fonte de música inválida.', 'VALIDATION_ERROR', 400);
            }
            $tribute->setMusicSource($source);
            if ($source === MusicSource::None) {
                $tribute->setMusicTrackId(null);
                $tribute->setMusicMediaId(null);
            } elseif ($source === MusicSource::Library) {
                $tribute->setMusicMediaId(null);
            } elseif ($source === MusicSource::Upload) {
                $tribute->setMusicTrackId(null);
            }
        }

        if (array_key_exists('music_track_id', $payload)) {
            $trackId = $payload['music_track_id'];
            if ($trackId === null || $trackId === '') {
                $tribute->setMusicTrackId(null);
                if ($tribute->getMusicSource() === MusicSource::Library) {
                    $tribute->setMusicSource(MusicSource::None);
                }
            } elseif (Uuid::isValid((string) $trackId)) {
                $track = $this->musicTrackRepository->findActiveById(Uuid::fromString((string) $trackId));
                if ($track === null) {
                    throw new TributeException('Faixa musical não encontrada.', 'MUSIC_TRACK_NOT_FOUND', 404);
                }
                if (!$tribute->getTemplate()->supportsMusic()) {
                    throw new TributeException('Template não suporta música.', 'MUSIC_NOT_SUPPORTED', 422);
                }
                $tribute->setMusicTrackId($track->getId());
                $tribute->setMusicSource(MusicSource::Library);
                $tribute->setMusicMediaId(null);
            } else {
                throw new TributeException('ID de faixa inválido.', 'VALIDATION_ERROR', 400);
            }
        }

        if (array_key_exists('content_json', $payload) && is_array($payload['content_json'])) {
            $content = $tribute->getContentJson();
            $incoming = $payload['content_json'];

            if (isset($incoming['effects']) && is_array($incoming['effects'])) {
                foreach ($incoming['effects'] as $effect) {
                    if (!is_string($effect) || !in_array($effect, TributeEffects::ALLOWED, true)) {
                        throw new TributeException('Efeito visual inválido.', 'INVALID_EFFECT', 400);
                    }
                }
                $content['effects'] = array_values($incoming['effects']);
            }

            $content = $this->mergeContentCustomization($content, $incoming);

            $tribute->setContentJson($content);
        }

        $this->tributeRepository->save($tribute);

        return $tribute;
    }

    public function archive(Tribute $tribute): void
    {
        if ($tribute->getStatus() === TributeStatus::Archived) {
            return;
        }

        $tribute->archive();
        $this->tributeRepository->save($tribute);
    }

    /**
     * Mescla, com validação, as chaves de personalização da experiência no
     * content_json. Estilo de apresentação, fontes, plano de fundo, ordem das
     * seções, efeitos de texto/foto, transições e textos específicos de layout
     * (ex.: pergunta do pedido) precisam ser persistidos para que a homenagem
     * seja renderizada como o usuário configurou.
     *
     * @param array<string, mixed> $content
     * @param array<string, mixed> $incoming
     *
     * @return array<string, mixed>
     */
    private function mergeContentCustomization(array $content, array $incoming): array
    {
        // Identificadores curtos (id de preset ou família CSS). null/'' limpa.
        foreach (['style_id', 'presentation', 'font', 'background'] as $key) {
            if (!array_key_exists($key, $incoming)) {
                continue;
            }
            $value = $incoming[$key];
            if ($value === null || $value === '') {
                unset($content[$key]);
            } elseif (is_string($value)) {
                $content[$key] = mb_substr(trim($value), 0, 80);
            } else {
                throw new TributeException(sprintf('Valor inválido para %s.', $key), 'VALIDATION_ERROR', 400);
            }
        }

        // Campos com valores fechados (enums). null/'' limpa.
        $enums = [
            'animation_speed' => ['slow', 'normal', 'fast'],
            'entrance' => ['fade', 'slide-up', 'zoom'],
            'photo_style' => ['slider', 'gallery', 'mosaic', 'polaroid'],
            'text_style' => ['default', 'typewriter'],
        ];
        foreach ($enums as $key => $allowed) {
            if (!array_key_exists($key, $incoming)) {
                continue;
            }
            $value = $incoming[$key];
            if ($value === null || $value === '') {
                unset($content[$key]);
            } elseif (is_string($value) && in_array($value, $allowed, true)) {
                $content[$key] = $value;
            } else {
                throw new TributeException(sprintf('Valor inválido para %s.', $key), 'VALIDATION_ERROR', 400);
            }
        }

        // Textos específicos de layout (ex.: pergunta do pedido interativo).
        foreach (['question', 'celebration'] as $key) {
            if (!array_key_exists($key, $incoming)) {
                continue;
            }
            $value = $incoming[$key];
            if ($value === null || $value === '') {
                unset($content[$key]);
            } elseif (is_string($value)) {
                $content[$key] = mb_substr(trim($value), 0, 300);
            } else {
                throw new TributeException(sprintf('Valor inválido para %s.', $key), 'VALIDATION_ERROR', 400);
            }
        }

        // Ordem/ativação das seções (lista de ids). [] limpa.
        if (array_key_exists('section_order', $incoming)) {
            $order = $incoming['section_order'];
            if ($order === null || $order === []) {
                unset($content['section_order']);
            } elseif (is_array($order)) {
                $clean = [];
                foreach ($order as $item) {
                    if (is_string($item) && $item !== '') {
                        $clean[] = mb_substr($item, 0, 80);
                    }
                }
                $content['section_order'] = array_slice($clean, 0, 40);
            } else {
                throw new TributeException('Ordem das seções inválida.', 'VALIDATION_ERROR', 400);
            }
        }

        // Autoria (nome de quem envia e assinatura). null/'' limpa.
        foreach (['sender_name', 'signature'] as $key) {
            if (!array_key_exists($key, $incoming)) {
                continue;
            }
            $value = $incoming[$key];
            if ($value === null || $value === '') {
                unset($content[$key]);
            } elseif (is_string($value)) {
                $content[$key] = mb_substr(trim($value), 0, 120);
            } else {
                throw new TributeException(sprintf('Valor inválido para %s.', $key), 'VALIDATION_ERROR', 400);
            }
        }

        // URL de vídeo (http/https). null/'' limpa.
        if (array_key_exists('video_url', $incoming)) {
            $value = $incoming['video_url'];
            if ($value === null || $value === '') {
                unset($content['video_url']);
            } elseif (is_string($value) && preg_match('#^https?://#i', trim($value)) === 1) {
                $content['video_url'] = mb_substr(trim($value), 0, 500);
            } else {
                throw new TributeException('URL de vídeo inválida.', 'VALIDATION_ERROR', 400);
            }
        }

        // Mensagens adicionais (lista de textos). [] limpa.
        if (array_key_exists('messages', $incoming)) {
            $messages = $incoming['messages'];
            if ($messages === null || $messages === []) {
                unset($content['messages']);
            } elseif (is_array($messages)) {
                $clean = [];
                foreach ($messages as $msg) {
                    if (is_string($msg) && trim($msg) !== '') {
                        $clean[] = mb_substr(trim($msg), 0, 2000);
                    }
                }
                $content['messages'] = array_slice($clean, 0, 50);
            } else {
                throw new TributeException('Lista de mensagens inválida.', 'VALIDATION_ERROR', 400);
            }
        }

        // Linha do tempo (lista de marcos). [] limpa.
        if (array_key_exists('timeline', $incoming)) {
            $timeline = $incoming['timeline'];
            if ($timeline === null || $timeline === []) {
                unset($content['timeline']);
            } elseif (is_array($timeline)) {
                $clean = [];
                foreach ($timeline as $item) {
                    if (!is_array($item)) {
                        throw new TributeException('Item da linha do tempo inválido.', 'VALIDATION_ERROR', 400);
                    }
                    $title = isset($item['title']) && is_string($item['title']) ? trim($item['title']) : '';
                    if ($title === '') {
                        continue;
                    }
                    $entry = ['title' => mb_substr($title, 0, 160)];
                    foreach (['date' => 40, 'description' => 2000] as $field => $max) {
                        if (isset($item[$field]) && is_string($item[$field]) && trim($item[$field]) !== '') {
                            $entry[$field] = mb_substr(trim($item[$field]), 0, $max);
                        }
                    }
                    if (isset($item['photo_url']) && is_string($item['photo_url']) && trim($item['photo_url']) !== '') {
                        $entry['photo_url'] = mb_substr(trim($item['photo_url']), 0, 500);
                    }
                    $clean[] = $entry;
                }
                $content['timeline'] = array_slice($clean, 0, 50);
            } else {
                throw new TributeException('Linha do tempo inválida.', 'VALIDATION_ERROR', 400);
            }
        }

        // Opções do player de música (autoplay/loop). Booleanos; ausência mantém.
        foreach (['music_autoplay', 'music_loop'] as $key) {
            if (!array_key_exists($key, $incoming)) {
                continue;
            }
            $value = $incoming[$key];
            if ($value === null) {
                unset($content[$key]);
            } elseif (is_bool($value)) {
                $content[$key] = $value;
            } else {
                throw new TributeException(sprintf('Valor inválido para %s.', $key), 'VALIDATION_ERROR', 400);
            }
        }

        // Informações de evento (data/local/mapa). null/[] limpa.
        if (array_key_exists('event_info', $incoming)) {
            $event = $incoming['event_info'];
            if ($event === null || $event === []) {
                unset($content['event_info']);
            } elseif (is_array($event)) {
                $entry = [];
                foreach (['date' => 40, 'location' => 200, 'map_url' => 500] as $field => $max) {
                    if (isset($event[$field]) && is_string($event[$field]) && trim($event[$field]) !== '') {
                        $entry[$field] = mb_substr(trim($event[$field]), 0, $max);
                    }
                }
                if ($entry === []) {
                    unset($content['event_info']);
                } else {
                    $content['event_info'] = $entry;
                }
            } else {
                throw new TributeException('Informações de evento inválidas.', 'VALIDATION_ERROR', 400);
            }
        }

        return $content;
    }

    private function assertEditable(Tribute $tribute): void
    {
        if (!$tribute->isEditable()) {
            throw new TributeException(
                'Esta homenagem não pode ser editada no status atual.',
                'TRIBUTE_NOT_EDITABLE',
                403,
            );
        }
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $value);
        if ($date === false) {
            throw new TributeException('Data especial inválida.', 'VALIDATION_ERROR', 400);
        }

        return $date;
    }
}
