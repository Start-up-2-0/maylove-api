<?php

declare(strict_types=1);

namespace App\Application\Map;

use App\Domain\Map\Exception\CoupleMapException;
use App\Domain\Map\MapPlaceType;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Entity\MapPlace;
use App\Infrastructure\Persistence\Repository\MapPlaceRepository;

final class MapPlaceService
{
    private const MAX_PLACES = 30;

    public function __construct(
        private readonly MapPlaceRepository $placeRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(CoupleMap $map, array $payload): MapPlace
    {
        $this->assertEditable($map);

        if ($this->placeRepository->countActiveByMap($map) >= self::MAX_PLACES) {
            throw new CoupleMapException(
                sprintf('Limite de %d locais por mapa atingido.', self::MAX_PLACES),
                'PLACE_QUOTA_EXCEEDED',
                422,
            );
        }

        $placeType = MapPlaceType::tryFrom((string) ($payload['place_type'] ?? ''));
        if ($placeType === null) {
            throw new CoupleMapException('Tipo de local inválido.', 'VALIDATION_ERROR', 400);
        }

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new CoupleMapException('Título do local é obrigatório.', 'VALIDATION_ERROR', 422);
        }

        [$lat, $lng] = $this->parseCoordinates($payload);

        $place = new MapPlace(
            $map,
            $placeType,
            $title,
            $lat,
            $lng,
            $this->placeRepository->getNextSortOrder($map),
        );

        $this->applyPlacePayload($place, $payload);

        if (!empty($payload['is_highlight'])) {
            $this->placeRepository->clearHighlight($map);
            $place->setIsHighlight(true);
        }

        $this->placeRepository->save($place);

        return $place;
    }

    /** @return list<MapPlace> */
    public function list(CoupleMap $map): array
    {
        return $this->placeRepository->findActiveByMap($map);
    }

    public function get(CoupleMap $map, string $placeId): MapPlace
    {
        return $this->requirePlace($map, $placeId);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(CoupleMap $map, string $placeId, array $payload): MapPlace
    {
        $this->assertEditable($map);
        $place = $this->requirePlace($map, $placeId);

        if (array_key_exists('place_type', $payload)) {
            $placeType = MapPlaceType::tryFrom((string) $payload['place_type']);
            if ($placeType === null) {
                throw new CoupleMapException('Tipo de local inválido.', 'VALIDATION_ERROR', 400);
            }
            $place->setPlaceType($placeType);
        }

        if (array_key_exists('title', $payload)) {
            $title = trim((string) $payload['title']);
            if ($title === '') {
                throw new CoupleMapException('Título do local é obrigatório.', 'VALIDATION_ERROR', 422);
            }
            $place->setTitle($title);
        }

        if (array_key_exists('latitude', $payload) || array_key_exists('longitude', $payload)) {
            [$lat, $lng] = $this->parseCoordinates([
                'latitude' => $payload['latitude'] ?? $place->getLatitude(),
                'longitude' => $payload['longitude'] ?? $place->getLongitude(),
            ]);
            $place->setLatitude($lat);
            $place->setLongitude($lng);
        }

        $this->applyPlacePayload($place, $payload);

        if (array_key_exists('is_highlight', $payload)) {
            if ((bool) $payload['is_highlight']) {
                $this->placeRepository->clearHighlight($map, $place->getId());
                $place->setIsHighlight(true);
            } else {
                $place->setIsHighlight(false);
            }
        }

        $this->placeRepository->save($place);

        return $place;
    }

    public function delete(CoupleMap $map, string $placeId): void
    {
        $this->assertEditable($map);
        $place = $this->requirePlace($map, $placeId);
        $place->softDelete();
        $this->placeRepository->save($place);
    }

    /** @param list<string> $order */
    public function reorder(CoupleMap $map, array $order): void
    {
        $this->assertEditable($map);

        if ($order === []) {
            throw new CoupleMapException('Ordem de locais inválida.', 'VALIDATION_ERROR', 400);
        }

        $places = $this->placeRepository->findActiveByMap($map);
        $byId = [];
        foreach ($places as $place) {
            $byId[(string) $place->getId()] = $place;
        }

        foreach ($order as $placeId) {
            if (!isset($byId[$placeId])) {
                throw new CoupleMapException('Local inválido na ordenação.', 'VALIDATION_ERROR', 400);
            }
        }

        $sortOrder = 0;
        foreach ($order as $placeId) {
            $byId[$placeId]->setSortOrder($sortOrder++);
        }

        $this->placeRepository->flush();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyPlacePayload(MapPlace $place, array $payload): void
    {
        if (array_key_exists('subtitle', $payload)) {
            $place->setSubtitle(is_string($payload['subtitle']) ? trim($payload['subtitle']) ?: null : null);
        }

        if (array_key_exists('description', $payload)) {
            $desc = is_string($payload['description']) ? trim(strip_tags($payload['description'])) : null;
            $place->setDescription($desc !== '' ? $desc : null);
        }

        if (array_key_exists('memory_date', $payload)) {
            $place->setMemoryDate($this->parseDate($payload['memory_date']));
        }

        if (array_key_exists('memory_time', $payload)) {
            $place->setMemoryTime($this->parseTime($payload['memory_time']));
        }

        if (array_key_exists('address_label', $payload)) {
            $place->setAddressLabel(is_string($payload['address_label']) ? trim($payload['address_label']) ?: null : null);
        }

        if (array_key_exists('city', $payload)) {
            $place->setCity(is_string($payload['city']) ? trim($payload['city']) ?: null : null);
        }

        if (array_key_exists('country', $payload)) {
            $place->setCountry(is_string($payload['country']) ? strtoupper(trim($payload['country'])) ?: null : null);
        }

        if (array_key_exists('sentiment', $payload)) {
            $place->setSentiment(is_string($payload['sentiment']) ? trim($payload['sentiment']) ?: null : null);
        }

        if (array_key_exists('content_json', $payload) && is_array($payload['content_json'])) {
            $place->setContentJson($payload['content_json']);
        }
    }

    /** @param array<string, mixed> $payload @return array{0: float, 1: float} */
    private function parseCoordinates(array $payload): array
    {
        $lat = (float) ($payload['latitude'] ?? 0);
        $lng = (float) ($payload['longitude'] ?? 0);

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new CoupleMapException('Coordenadas inválidas.', 'INVALID_COORDINATES', 422);
        }

        return [$lat, $lng];
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);

        return $date !== false ? $date : null;
    }

    private function parseTime(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $time = \DateTimeImmutable::createFromFormat('!H:i:s', (string) $value)
            ?: \DateTimeImmutable::createFromFormat('!H:i', (string) $value);

        return $time !== false ? $time : null;
    }

    private function requirePlace(CoupleMap $map, string $placeId): MapPlace
    {
        if (!UuidValue::isValid($placeId)) {
            throw new CoupleMapException('Local não encontrado.', 'PLACE_NOT_FOUND', 404);
        }

        $place = $this->placeRepository->findActiveByIdAndMap(UuidValue::fromString($placeId), $map);
        if ($place === null) {
            throw new CoupleMapException('Local não encontrado.', 'PLACE_NOT_FOUND', 404);
        }

        return $place;
    }

    private function assertEditable(CoupleMap $map): void
    {
        if (!$map->isEditable()) {
            throw new CoupleMapException('Mapa não pode ser editado.', 'MAP_NOT_EDITABLE', 403);
        }
    }
}
