<?php

declare(strict_types=1);

namespace App\Application\Album;

use App\Infrastructure\Persistence\Entity\Album;

final class AlbumQrService
{
    public function __construct(
        private readonly string $appUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Album $album, ?string $memoryId = null): array
    {
        $base = rtrim($this->appUrl, '/');
        $publicUrl = $base.'/h/'.$album->getSlug();
        $deepLink = $memoryId !== null ? $publicUrl.'?m='.$memoryId : $publicUrl;

        return [
            'slug' => $album->getSlug(),
            'public_url' => $publicUrl,
            'deep_link' => $deepLink,
            'memory_id' => $memoryId,
        ];
    }
}
