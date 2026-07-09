<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger\Handler;

use App\Domain\Shared\UuidValue;
use App\Infrastructure\Messenger\Message\RecordTributeViewMessage;
use App\Infrastructure\Persistence\Entity\TributeView;
use App\Infrastructure\Persistence\Repository\TributeRepository;
use App\Infrastructure\Persistence\Repository\TributeViewRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RecordTributeViewHandler
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
        private readonly TributeViewRepository $tributeViewRepository,
    ) {
    }

    public function __invoke(RecordTributeViewMessage $message): void
    {
        if (!UuidValue::isValid($message->tributeId) || !UuidValue::isValid($message->sessionId)) {
            return;
        }

        $tribute = $this->tributeRepository->find(UuidValue::fromString($message->tributeId));
        if ($tribute === null) {
            return;
        }

        $this->tributeViewRepository->save(new TributeView($tribute, $message->sessionId));
        $this->tributeRepository->incrementViewsCount($tribute->getId());
    }
}
