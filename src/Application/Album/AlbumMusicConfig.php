<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Infrastructure\Persistence\Entity\Album;

/** Helpers para book_config.music do álbum. */
final class AlbumMusicConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function get(Album $album): array
    {
        $config = $album->getBookConfig() ?? [];
        $music = $config['music'] ?? null;

        return is_array($music) ? $music : [];
    }

    /**
     * @param array<string, mixed> $music
     */
    public static function set(Album $album, array $music): void
    {
        $config = $album->getBookConfig() ?? [];
        $config['music'] = $music;
        $album->setBookConfig($config);
    }

    /**
     * @param array<string, mixed> $patch
     *
     * @return array<string, mixed>
     */
    public static function merge(Album $album, array $patch): array
    {
        $music = array_merge(self::defaults(), self::get($album), $patch);
        self::set($album, $music);

        return $music;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'source' => 'none',
            'autoplay' => true,
            'loop' => true,
            'duration_seconds' => 0,
            'start_seconds' => 0,
            'end_seconds' => 0,
            'import' => [
                'status' => null,
                'error' => null,
                'media_id' => null,
            ],
        ];
    }
}
