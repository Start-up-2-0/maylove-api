<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Application\Tribute\TributePublishedDispatcher;
use App\Domain\Tribute\BillingModeUsed;
use App\Infrastructure\Persistence\Entity\Tribute;
use App\Infrastructure\Persistence\Repository\TributeRepository;

final class TributePublicationService
{
    public function __construct(
        private readonly TributeRepository $tributeRepository,
        private readonly TributePublishedDispatcher $publishedDispatcher,
    ) {
    }

    public function publishFromPayment(Tribute $tribute, BillingModeUsed $billingMode): void
    {
        if ($tribute->getStatus()->isPublic()) {
            return;
        }

        $tribute->markPublished($billingMode);
        $this->tributeRepository->save($tribute);
        $this->publishedDispatcher->dispatch($tribute, $tribute->getUser());
    }
}
