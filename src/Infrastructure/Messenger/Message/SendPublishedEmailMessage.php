<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final readonly class SendPublishedEmailMessage
{
    public function __construct(
        public string $messageId,
        public string $tributeId,
        public string $recipientEmail,
        public string $recipientName,
        public string $tributeTitle,
        public string $publicUrl,
    ) {
    }
}
