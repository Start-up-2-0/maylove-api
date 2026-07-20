<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Tribute\BillingModeUsed;
use App\Infrastructure\Persistence\Entity\CoupleMap;
use App\Infrastructure\Persistence\Repository\CoupleMapRepository;

final class CoupleMapPublicationService
{
    public function __construct(
        private readonly CoupleMapRepository $mapRepository,
    ) {
    }

    public function publishFromPayment(CoupleMap $map): void
    {
        if ($map->getStatus()->isPublic()) {
            return;
        }

        $map->markPublished(BillingModeUsed::PerTribute);
        $this->mapRepository->save($map);
    }
}
