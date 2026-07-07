<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final readonly class SendVerificationEmailMessage
{
    public function __construct(
        public string $messageId,
        public string $recipientEmail,
        public string $recipientName,
        public string $verificationUrl,
        public int $validityHours,
    ) {
    }
}
