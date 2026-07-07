<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Infrastructure\Email\PublishedEmailTemplate;
use App\Infrastructure\Email\ResendEmailGateway;
use App\Infrastructure\Messenger\Message\SendPublishedEmailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendPublishedEmailHandler
{
    public function __construct(
        private readonly ResendEmailGateway $emailGateway,
    ) {
    }

    public function __invoke(SendPublishedEmailMessage $message): void
    {
        $html = PublishedEmailTemplate::render(
            $message->recipientName,
            $message->tributeTitle,
            $message->publicUrl,
        );

        $this->emailGateway->send(
            $message->recipientEmail,
            'Sua homenagem está no ar — MayLove',
            $html,
            $message->messageId,
        );
    }
}
