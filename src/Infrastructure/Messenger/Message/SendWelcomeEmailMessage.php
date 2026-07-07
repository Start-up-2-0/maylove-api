<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final readonly class SendWelcomeEmailMessage
{
    public function __construct(
        public string $messageId,
        public string $recipientEmail,
        public string $recipientName,
        public string $dashboardUrl,
    ) {
    }
}
