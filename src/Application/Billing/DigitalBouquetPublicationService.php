<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Tribute\BillingModeUsed;
use App\Infrastructure\Persistence\Entity\DigitalBouquet;
use App\Infrastructure\Persistence\Repository\DigitalBouquetRepository;

final class DigitalBouquetPublicationService
{
    public function __construct(
        private readonly DigitalBouquetRepository $bouquetRepository,
    ) {
    }

    public function publishFromPayment(DigitalBouquet $bouquet): void
    {
        if ($bouquet->getStatus()->isPublic()) {
            return;
        }

        $bouquet->markPublished(BillingModeUsed::PerTribute);
        $this->bouquetRepository->save($bouquet);
    }
}
