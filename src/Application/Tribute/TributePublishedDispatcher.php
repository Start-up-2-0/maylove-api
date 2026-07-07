<?php

declare(strict_types=1);

namespace App\Application\Tribute;

use App\Infrastructure\Messenger\Message\GenerateOgImageMessage;
use App\Infrastructure\Messenger\Message\SendPublishedEmailMessage;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Entity\User;
use Symfony\Component\Messenger\MessageBusInterface;

final class TributePublishedDispatcher
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly string $appUrl,
    ) {
    }

    public function dispatch(Tribute $tribute, User $user): void
    {
        $tributeId = (string) $tribute->getId();
        $publicUrl = rtrim($this->appUrl, '/').'/h/'.$tribute->getSlug();
        $title = $tribute->getTitle() ?? $tribute->getHonoreeName() ?? 'Sua homenagem';

        $this->messageBus->dispatch(new GenerateOgImageMessage($tributeId));
        $this->messageBus->dispatch(new SendPublishedEmailMessage(
            messageId: 'published:'.$tributeId,
            tributeId: $tributeId,
            recipientEmail: $user->getEmail(),
            recipientName: $user->getName(),
            tributeTitle: $title,
            publicUrl: $publicUrl,
        ));
    }
}
