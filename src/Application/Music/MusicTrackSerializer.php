<?php

declare(strict_types=1);

namespace App\Application\Music;

use App\Infrastructure\Persistence\Entity\Music\MusicTrack;

final class MusicTrackSerializer
{
    public function __construct(
        private readonly MusicTrackUrlResolver $urlResolver,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(MusicTrack $track): array
    {
        return [
            'id' => (string) $track->getId(),
            'slug' => $track->getSlug(),
            'title' => $track->getTitle(),
            'artist' => $track->getArtist(),
            'duration_seconds' => $track->getDurationSeconds(),
            'category' => $track->getCategory(),
            'preview_url' => $this->urlResolver->resolvePreviewUrl($track),
            'file_url' => $this->urlResolver->resolveFileUrl($track),
        ];
    }
}
