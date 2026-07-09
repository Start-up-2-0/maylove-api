<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final class ImportMusicFromYoutubeMessage
{
    public function __construct(
        public readonly string $tributeId,
        public readonly string $url,
        public readonly string $userId,
    ) {
    }
}
