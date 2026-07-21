<?php

declare(strict_types=1);

namespace App\Application\Bouquet;

use App\Application\Billing\SubscriptionCapabilityService;
use App\Domain\Bouquet\DigitalBouquetStatus;
use App\Domain\Bouquet\Exception\DigitalBouquetException;
use App\Domain\Tribute\BillingModeUsed;
use App\Infrastructure\Persistence\Entity\DigitalBouquet;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\DigitalBouquetRepository;

final class DigitalBouquetPublishService
{
    public function __construct(
        private readonly DigitalBouquetValidationService $validationService,
        private readonly SubscriptionCapabilityService $subscriptionCapability,
        private readonly DigitalBouquetRepository $bouquetRepository,
        private readonly bool $billingEnabled,
    ) {
    }

    public function publish(DigitalBouquet $bouquet, User $user): DigitalBouquet
    {
        if ($bouquet->getStatus() === DigitalBouquetStatus::Published) {
            throw new DigitalBouquetException('Buquê já publicado.', 'ALREADY_PUBLISHED', 409);
        }

        if (!$bouquet->isEditable()) {
            throw new DigitalBouquetException('Buquê não pode ser publicado.', 'BOUQUET_NOT_EDITABLE', 403);
        }

        $validation = $this->validationService->validate($bouquet);
        if (!$validation['valid']) {
            throw new DigitalBouquetException('Buquê incompleto para publicação.', 'VALIDATION_FAILED', 422);
        }

        $userId = (string) $user->getId();
        if ($this->billingEnabled && !$this->subscriptionCapability->canPublishFree($userId)) {
            throw new DigitalBouquetException(
                'Assinatura ativa necessária. Use o checkout para pagamento avulso.',
                'SUBSCRIPTION_REQUIRED',
                402,
            );
        }

        $billingMode = $this->billingEnabled
            ? ($this->subscriptionCapability->getBillingMode($userId) ?? BillingModeUsed::SubscriptionMonthly)
            : BillingModeUsed::PerTribute;

        $bouquet->markPublished($billingMode);
        $this->bouquetRepository->save($bouquet);

        return $bouquet;
    }
}
