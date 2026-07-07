<?php

declare(strict_types=1);

namespace App\Application\Music;

use App\Infrastructure\Persistence\Entity\Music\MusicTrack;

final class MusicTrackUrlResolver
{
    public function __construct(
        private readonly string $storagePublicUrl,
    ) {
    }

    public function resolveFileUrl(MusicTrack $track): string
    {
        return $this->resolvePath($track->getRelativePath());
    }

    public function resolvePreviewUrl(MusicTrack $track): string
    {
        if ($track->getPreviewUrl() !== null && $track->getPreviewUrl() !== '') {
            if (str_starts_with($track->getPreviewUrl(), 'http://') || str_starts_with($track->getPreviewUrl(), 'https://')) {
                return $track->getPreviewUrl();
            }

            return $this->resolvePath($track->getPreviewUrl());
        }

        return $this->resolveFileUrl($track);
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim($this->storagePublicUrl, '/').'/'.ltrim($path, '/');
    }
}
