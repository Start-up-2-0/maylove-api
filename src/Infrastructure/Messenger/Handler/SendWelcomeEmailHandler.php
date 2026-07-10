<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Infrastructure\Email\ResendEmailGateway;
use App\Infrastructure\Email\WelcomeEmailTemplate;
use App\Infrastructure\Messenger\Message\SendWelcomeEmailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendWelcomeEmailHandler
{
    public function __construct(
        private readonly ResendEmailGateway $emailGateway,
    ) {
    }

    public function __invoke(SendWelcomeEmailMessage $message): void
    {
        $html = WelcomeEmailTemplate::render(
            $message->recipientName,
            $message->dashboardUrl,
        );

        $this->emailGateway->send(
            $message->recipientEmail,
            'Bem-vindo ao MayLov',
            $html,
            $message->messageId,
        );
    }
}
