<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Infrastructure\Email\ResendEmailGateway;
use App\Infrastructure\Email\VerificationEmailTemplate;
use App\Infrastructure\Messenger\Message\SendVerificationEmailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendVerificationEmailHandler
{
    public function __construct(
        private readonly ResendEmailGateway $emailGateway,
    ) {
    }

    public function __invoke(SendVerificationEmailMessage $message): void
    {
        $html = VerificationEmailTemplate::render(
            $message->recipientName,
            $message->verificationUrl,
            $message->validityHours,
        );

        $this->emailGateway->send(
            $message->recipientEmail,
            'Confirme seu cadastro no MayLove',
            $html,
            $message->messageId,
        );
    }
}
