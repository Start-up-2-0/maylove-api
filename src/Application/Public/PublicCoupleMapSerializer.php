<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Application\Map\CoupleMapSerializer;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Repository\CoupleMapRepository;

final class PublicCoupleMapSerializer
{
    public function __construct(
        private readonly CoupleMapSerializer $serializer,
        private readonly CoupleMapRepository $mapRepository,
    ) {
    }

    /** @return array<string, mixed> */
    public function serialize(CoupleMap $map): array
    {
        $data = $this->serializer->serializeDetail($map, includePlaceMedia: true);
        $data['public_url'] = sprintf('https://maylove.com.br/map/%s', $map->getSlug());

        $highlight = null;
        foreach ($data['places'] as $place) {
            if (!empty($place['is_highlight'])) {
                $cover = null;
                foreach ($place['media'] ?? [] as $media) {
                    if (($media['media_type'] ?? '') === 'photo') {
                        $cover = $media['url_thumbnail'] ?? $media['url'] ?? null;
                        break;
                    }
                }
                $highlight = [
                    'id' => $place['id'],
                    'title' => $place['title'],
                    'latitude' => $place['latitude'],
                    'longitude' => $place['longitude'],
                    'cover_url' => $cover,
                ];
                break;
            }
        }

        $data['highlight_place'] = $highlight;
        $data['route_polyline'] = $map->isShowRoute()
            ? $this->buildRoutePoints($data['places'])
            : null;

        return $data;
    }

    /**
     * @param list<array<string, mixed>> $places
     *
     * @return list<array{lat: float, lng: float}>|null
     */
    private function buildRoutePoints(array $places): ?array
    {
        if (count($places) < 2) {
            return null;
        }

        usort($places, static function (array $a, array $b): int {
            $dateA = $a['memory_date'] ?? '';
            $dateB = $b['memory_date'] ?? '';

            if ($dateA !== $dateB) {
                return $dateA <=> $dateB;
            }

            return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
        });

        return array_map(static fn (array $p) => [
            'lat' => (float) $p['latitude'],
            'lng' => (float) $p['longitude'],
        ], $places);
    }
}
