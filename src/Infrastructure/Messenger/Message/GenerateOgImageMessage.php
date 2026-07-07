<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final readonly class GenerateOgImageMessage
{
    public function __construct(
        public string $tributeId,
    ) {
    }
}
