<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Message;

final readonly class ProcessMercadoPagoWebhookMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $externalEventId,
        public string $eventType,
        public array $payload,
    ) {
    }
}
