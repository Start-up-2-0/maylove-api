<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Tribute\Exception\TributeException;
use App\Infrastructure\Messenger\Message\RecordTributeViewMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class TributeViewService
{
    public function __construct(
        private readonly PublicTributeQueryService $queryService,
        private readonly TributeViewDebounceService $debounceService,
        private readonly ViewRateLimitService $rateLimitService,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function recordView(string $slug, string $sessionId): void
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '' || !Uuid::isValid($sessionId)) {
            throw new TributeException('session_id inválido.', 'VALIDATION_ERROR', 400);
        }

        if (!$this->rateLimitService->canRecordView($slug, $sessionId)) {
            return;
        }

        $tribute = $this->queryService->getPublishedBySlug($slug);
        $tributeId = (string) $tribute->getId();

        if (!$this->debounceService->shouldRecord($tributeId, $sessionId)) {
            return;
        }

        $this->messageBus->dispatch(new RecordTributeViewMessage($tributeId, $sessionId));
    }
}
