<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final readonly class RecordTributeViewMessage
{
    public function __construct(
        public string $tributeId,
        public string $sessionId,
    ) {
    }
}
