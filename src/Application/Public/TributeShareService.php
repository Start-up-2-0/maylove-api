<?php

declare(strict_types=1);

namespace App\Application\Public;

use App\Domain\Tribute\Exception\TributeException;
use App\Infrastructure\Persistence\Repository\TributeRepository;
use App\Infrastructure\RateLimit\InMemoryRateLimiter;
use Symfony\Component\Uid\Uuid;

final class TributeShareService
{
    public function __construct(
        private readonly PublicTributeQueryService $queryService,
        private readonly TributeRepository $tributeRepository,
        private readonly InMemoryRateLimiter $rateLimiter,
    ) {
    }

    public function record(string $slug, string $sessionId): void
    {
        if (!Uuid::isValid($sessionId)) {
            throw new TributeException('session_id inválido.', 'VALIDATION_ERROR', 400);
        }

        $tribute = $this->queryService->getPublishedBySlug($slug);
        if (!$this->rateLimiter->claim('maylove:share:'.$tribute->getId().':'.$sessionId, 30)) {
            return;
        }

        $this->tributeRepository->incrementSharesCount($tribute->getId());
    }
}
