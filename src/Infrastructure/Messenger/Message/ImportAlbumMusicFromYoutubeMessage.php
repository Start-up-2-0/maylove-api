<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final class ImportAlbumMusicFromYoutubeMessage
{
    public function __construct(
        public readonly string $albumId,
        public readonly string $url,
        public readonly string $userId,
    ) {
    }
}
