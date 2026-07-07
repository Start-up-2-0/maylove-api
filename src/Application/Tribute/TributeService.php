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
            if (isset($payload['content_json']['effects']) && is_array($payload['content_json']['effects'])) {
                foreach ($payload['content_json']['effects'] as $effect) {
                    if (!is_string($effect) || !in_array($effect, TributeEffects::ALLOWED, true)) {
                        throw new TributeException('Efeito visual inválido.', 'INVALID_EFFECT', 400);
                    }
                }
                $content['effects'] = array_values($payload['content_json']['effects']);
            }
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
