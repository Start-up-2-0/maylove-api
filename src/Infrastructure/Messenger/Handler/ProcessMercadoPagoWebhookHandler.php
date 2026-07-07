<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Application\Billing\MercadoPagoWebhookProcessor;
use App\Infrastructure\Messenger\Message\ProcessMercadoPagoWebhookMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProcessMercadoPagoWebhookHandler
{
    public function __construct(
        private readonly MercadoPagoWebhookProcessor $webhookProcessor,
    ) {
    }

    public function __invoke(ProcessMercadoPagoWebhookMessage $message): void
    {
        $this->webhookProcessor->process(
            $message->externalEventId,
            $message->eventType,
            $message->payload,
        );
    }
}
