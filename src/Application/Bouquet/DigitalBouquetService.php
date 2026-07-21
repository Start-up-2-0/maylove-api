<?php

declare(strict_types=1);

namespace App\Application\Bouquet;

use App\Domain\Bouquet\Exception\DigitalBouquetException;
use App\Domain\Bouquet\LetterDesign;
use App\Domain\Bouquet\WrapColor;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Persistence\Entity\DigitalBouquet;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\DigitalBouquetRepository;
use App\Infrastructure\Persistence\Repository\UserRepository;

final class DigitalBouquetService
{
    public function __construct(
        private readonly DigitalBouquetRepository $bouquetRepository,
        private readonly DigitalBouquetSlugGenerator $slugGenerator,
        private readonly UserRepository $userRepository,
        private readonly bool $emailVerificationEnabled,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(User $user, array $payload = []): DigitalBouquet
    {
        $managedUser = $this->userRepository->find($user->getId());
        if ($managedUser === null) {
            throw new DigitalBouquetException('Usuário não encontrado.', 'USER_NOT_FOUND', 404);
        }

        if ($this->emailVerificationEnabled && !$managedUser->isEmailVerified()) {
            throw new DigitalBouquetException(
                'Confirme seu e-mail antes de criar um buquê.',
                'EMAIL_NOT_VERIFIED',
                403,
            );
        }

        $title = trim((string) ($payload['title'] ?? 'Meu buquê digital'));
        if ($title === '') {
            $title = 'Meu buquê digital';
        }

        $bouquet = new DigitalBouquet(
            $managedUser,
            $this->slugGenerator->generateUnique(),
            $title,
        );

        $this->bouquetRepository->save($bouquet);

        return $bouquet;
    }

    /** @return list<DigitalBouquet> */
    public function listByUser(User $user, ?string $status = null): array
    {
        $bouquetStatus = null;
        if ($status !== null && $status !== '') {
            $bouquetStatus = \App\Domain\Bouquet\DigitalBouquetStatus::tryFrom($status);
        }

        return $this->bouquetRepository->findByUser($user, $bouquetStatus);
    }

    public function getOwned(string $id, User $user): DigitalBouquet
    {
        if (!UuidValue::isValid($id)) {
            throw new DigitalBouquetException('Buquê não encontrado.', 'BOUQUET_NOT_FOUND', 404);
        }

        $bouquet = $this->bouquetRepository->findOwnedById(UuidValue::fromString($id), $user->getId());
        if ($bouquet === null) {
            throw new DigitalBouquetException('Buquê não encontrado.', 'BOUQUET_NOT_FOUND', 404);
        }

        return $bouquet;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(DigitalBouquet $bouquet, array $payload): DigitalBouquet
    {
        if (!$bouquet->isEditable()) {
            throw new DigitalBouquetException('Buquê não pode ser editado.', 'BOUQUET_NOT_EDITABLE', 403);
        }

        if (array_key_exists('title', $payload)) {
            $title = trim((string) $payload['title']);
            if ($title === '') {
                throw new DigitalBouquetException('Título é obrigatório.', 'VALIDATION_ERROR', 422);
            }
            $bouquet->setTitle($title);
        }

        if (array_key_exists('stems', $payload) && is_array($payload['stems'])) {
            $stems = array_values(array_filter(
                array_map(static fn ($item) => trim((string) $item), $payload['stems']),
                static fn (string $item) => $item !== '',
            ));
            if (count($stems) > DigitalBouquet::MAX_STEMS) {
                throw new DigitalBouquetException(
                    sprintf('Máximo de %d flores.', DigitalBouquet::MAX_STEMS),
                    'VALIDATION_ERROR',
                    422,
                );
            }
            $bouquet->setStemsJson($stems);
        }

        if (array_key_exists('wrap_color', $payload)) {
            $wrap = WrapColor::tryFrom((string) $payload['wrap_color']);
            if ($wrap === null) {
                throw new DigitalBouquetException('Cor de embrulho inválida.', 'VALIDATION_ERROR', 422);
            }
            $bouquet->setWrapColor($wrap);
        }

        if (array_key_exists('recipient_name', $payload)) {
            $bouquet->setRecipientName(trim((string) $payload['recipient_name']));
        }

        if (array_key_exists('sender_name', $payload)) {
            $bouquet->setSenderName(trim((string) $payload['sender_name']));
        }

        if (array_key_exists('letter_body', $payload)) {
            $bouquet->setLetterBody(trim((string) $payload['letter_body']));
        }

        if (array_key_exists('letter_design', $payload)) {
            $design = LetterDesign::tryFrom((string) $payload['letter_design']);
            if ($design === null) {
                throw new DigitalBouquetException('Design de carta inválido.', 'VALIDATION_ERROR', 422);
            }
            $bouquet->setLetterDesign($design);
        }

        if (array_key_exists('content_json', $payload) && is_array($payload['content_json'])) {
            $bouquet->setContentJson($payload['content_json']);
        }

        $this->bouquetRepository->save($bouquet);

        return $bouquet;
    }

    public function archive(DigitalBouquet $bouquet): void
    {
        $bouquet->markArchived();
        $this->bouquetRepository->save($bouquet);
    }
}
