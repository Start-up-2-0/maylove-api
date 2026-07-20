<?php

declare(strict_types=1);

namespace App\Application\Map;

use App\Domain\Map\Exception\CoupleMapException;
use App\Domain\Map\MapStyle;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\CoupleMapRepository;
use App\Infrastructure\Persistence\Repository\MapPlaceRepository;
use App\Infrastructure\Persistence\Repository\UserRepository;

final class CoupleMapService
{
    public function __construct(
        private readonly CoupleMapRepository $mapRepository,
        private readonly MapPlaceRepository $placeRepository,
        private readonly CoupleMapSlugGenerator $slugGenerator,
        private readonly UserRepository $userRepository,
        private readonly bool $emailVerificationEnabled,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(User $user, array $payload = []): CoupleMap
    {
        $managedUser = $this->userRepository->find($user->getId());
        if ($managedUser === null) {
            throw new CoupleMapException('Usuário não encontrado.', 'USER_NOT_FOUND', 404);
        }

        if ($this->emailVerificationEnabled && !$managedUser->isEmailVerified()) {
            throw new CoupleMapException(
                'Confirme seu e-mail antes de criar um mapa.',
                'EMAIL_NOT_VERIFIED',
                403,
            );
        }

        $title = trim((string) ($payload['title'] ?? 'Nosso mapa do casal'));
        $coupleNames = trim((string) ($payload['couple_names'] ?? 'Nós dois'));

        if ($title === '') {
            $title = 'Nosso mapa do casal';
        }
        if ($coupleNames === '') {
            $coupleNames = 'Nós dois';
        }

        $map = new CoupleMap(
            $managedUser,
            $this->slugGenerator->generateUnique(),
            $title,
            $coupleNames,
        );

        if (isset($payload['subtitle']) && is_string($payload['subtitle'])) {
            $map->setSubtitle(trim($payload['subtitle']) ?: null);
        }

        $style = MapStyle::tryFrom((string) ($payload['map_style'] ?? ''));
        if ($style !== null) {
            $map->setMapStyle($style);
        }

        if (array_key_exists('show_route', $payload)) {
            $map->setShowRoute((bool) $payload['show_route']);
        }

        $this->mapRepository->save($map);

        return $map;
    }

    /** @return list<CoupleMap> */
    public function listByUser(User $user, ?string $status = null): array
    {
        $mapStatus = null;
        if ($status !== null && $status !== '') {
            $mapStatus = \App\Domain\Map\CoupleMapStatus::tryFrom($status);
        }

        return $this->mapRepository->findByUser($user, $mapStatus);
    }

    public function getOwned(string $id, User $user): CoupleMap
    {
        if (!UuidValue::isValid($id)) {
            throw new CoupleMapException('Mapa não encontrado.', 'MAP_NOT_FOUND', 404);
        }

        $map = $this->mapRepository->findOwnedById(UuidValue::fromString($id), $user->getId());
        if ($map === null) {
            throw new CoupleMapException('Mapa não encontrado.', 'MAP_NOT_FOUND', 404);
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(CoupleMap $map, array $payload): CoupleMap
    {
        if (!$map->isEditable()) {
            throw new CoupleMapException('Mapa não pode ser editado.', 'MAP_NOT_EDITABLE', 403);
        }

        if (array_key_exists('title', $payload)) {
            $title = trim((string) $payload['title']);
            if ($title === '') {
                throw new CoupleMapException('Título é obrigatório.', 'VALIDATION_ERROR', 422);
            }
            $map->setTitle($title);
        }

        if (array_key_exists('subtitle', $payload)) {
            $map->setSubtitle(is_string($payload['subtitle']) ? trim($payload['subtitle']) ?: null : null);
        }

        if (array_key_exists('couple_names', $payload)) {
            $names = trim((string) $payload['couple_names']);
            if ($names === '') {
                throw new CoupleMapException('Nomes do casal são obrigatórios.', 'VALIDATION_ERROR', 422);
            }
            $map->setCoupleNames($names);
        }

        if (array_key_exists('map_style', $payload)) {
            $style = MapStyle::tryFrom((string) $payload['map_style']);
            if ($style === null) {
                throw new CoupleMapException('Estilo de mapa inválido.', 'VALIDATION_ERROR', 422);
            }
            $map->setMapStyle($style);
        }

        if (array_key_exists('show_route', $payload)) {
            $map->setShowRoute((bool) $payload['show_route']);
        }

        if (array_key_exists('center_lat', $payload)) {
            $map->setCenterLat($this->nullableFloat($payload['center_lat']));
        }

        if (array_key_exists('center_lng', $payload)) {
            $map->setCenterLng($this->nullableFloat($payload['center_lng']));
        }

        if (array_key_exists('default_zoom', $payload)) {
            $zoom = $payload['default_zoom'];
            $map->setDefaultZoom($zoom === null ? null : (int) $zoom);
        }

        if (array_key_exists('content_json', $payload) && is_array($payload['content_json'])) {
            $map->setContentJson($payload['content_json']);
        }

        $this->mapRepository->save($map);

        return $map;
    }

    public function archive(CoupleMap $map): void
    {
        $map->markArchived();
        $this->mapRepository->save($map);
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
