<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\Exception\BillingException;
use App\Domain\Billing\ProductBillingMode;
use App\Domain\Billing\SubscriptionStatus;
use App\Infrastructure\Payment\MercadoPagoClient;
use App\Infrastructure\Persistence\Entity\Billing\UserSubscription;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\Billing\ProductPriceRepository;
use App\Infrastructure\Persistence\Repository\Billing\ProductRepository;
use App\Infrastructure\Persistence\Repository\Billing\UserSubscriptionRepository;

final class SubscriptionService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductPriceRepository $productPriceRepository,
        private readonly UserSubscriptionRepository $subscriptionRepository,
        private readonly MercadoPagoClient $mercadoPagoClient,
        private readonly SubscriptionCapabilityService $subscriptionCapability,
        private readonly string $frontendUrl,
        private readonly string $notificationUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubscription(User $user): array
    {
        $subscription = $this->subscriptionRepository->findByUser($user);

        if ($subscription === null) {
            return [
                'has_subscription' => false,
                'status' => null,
                'billing_mode' => null,
                'current_period_end' => null,
                'cancel_at_period_end' => false,
            ];
        }

        return $this->serialize($subscription);
    }

    /**
     * @return array<string, mixed>
     */
    public function startCheckout(User $user, string $billingMode): array
    {
        $mode = ProductBillingMode::tryFrom($billingMode);
        if ($mode === null || $mode === ProductBillingMode::PerTribute) {
            throw new BillingException('Modo de assinatura inválido.', 'VALIDATION_ERROR', 400);
        }

        $product = $this->productRepository->findActivePremium();
        if ($product === null) {
            throw new BillingException('Produto não encontrado.', 'PRODUCT_NOT_FOUND', 404);
        }

        $price = $this->productPriceRepository->findByProductAndMode($product, $mode);
        if ($price === null) {
            throw new BillingException('Preço não encontrado.', 'PRODUCT_NOT_FOUND', 404);
        }

        $subscription = $this->subscriptionRepository->findByUser($user);

        if ($subscription === null) {
            $subscription = new UserSubscription($user, $product, $price);
        } else {
            if ($subscription->hasActiveBenefit()) {
                throw new BillingException('Assinatura já ativa.', 'SUBSCRIPTION_ALREADY_ACTIVE', 409);
            }

            if ($subscription->getStatus() === SubscriptionStatus::Pending && $subscription->getCheckoutUrl() !== null) {
                return $this->serializeCheckout($subscription);
            }

            $subscription->prepareCheckout($product, $price);
        }

        $externalReference = 'subscription:'.(string) $subscription->getId();
        $preference = $this->mercadoPagoClient->createCheckoutPreference(
            $this->buildSubscriptionPreference($user, $price, $externalReference),
        );

        $subscription->setCheckoutSession(
            (string) ($preference['id'] ?? ''),
            $this->mercadoPagoClient->resolveCheckoutUrl($preference),
        );
        $this->subscriptionRepository->save($subscription);

        return $this->serializeCheckout($subscription);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(User $user): array
    {
        $subscription = $this->subscriptionRepository->findByUser($user);
        if ($subscription === null || !$subscription->hasActiveBenefit()) {
            throw new BillingException('Nenhuma assinatura ativa para cancelar.', 'SUBSCRIPTION_NOT_FOUND', 404);
        }

        $subscription->cancel();
        $this->subscriptionRepository->save($subscription);
        $this->subscriptionCapability->invalidateCache((string) $user->getId());

        return $this->serialize($subscription);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(UserSubscription $subscription): array
    {
        return [
            'has_subscription' => $subscription->hasActiveBenefit(),
            'status' => $subscription->getStatus()->value,
            'billing_mode' => $subscription->getProductPrice()->getBillingMode()->value,
            'current_period_start' => $subscription->getCurrentPeriodStart()?->format(\DateTimeInterface::ATOM),
            'current_period_end' => $subscription->getCurrentPeriodEnd()?->format(\DateTimeInterface::ATOM),
            'cancel_at_period_end' => $subscription->isCancelAtPeriodEnd(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCheckout(UserSubscription $subscription): array
    {
        return [
            'subscription_id' => (string) $subscription->getId(),
            'status' => $subscription->getStatus()->value,
            'billing_mode' => $subscription->getProductPrice()->getBillingMode()->value,
            'price_cents' => $subscription->getProductPrice()->getPriceCents(),
            'checkout_url' => $subscription->getCheckoutUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSubscriptionPreference(User $user, \App\Infrastructure\Persistence\Entity\Billing\ProductPrice $price, string $externalReference): array
    {
        $mode = $price->getBillingMode();
        $autoRecurring = match ($mode) {
            ProductBillingMode::SubscriptionMonthly => [
                'frequency' => 1,
                'frequency_type' => 'months',
                'transaction_amount' => $price->getPriceAmount(),
                'currency_id' => 'BRL',
            ],
            ProductBillingMode::SubscriptionYearly => [
                'frequency' => 12,
                'frequency_type' => 'months',
                'transaction_amount' => $price->getPriceAmount(),
                'currency_id' => 'BRL',
            ],
            default => throw new BillingException('Modo inválido para assinatura.', 'VALIDATION_ERROR', 400),
        };

        return [
            'items' => [[
                'title' => 'MayLove Premium — Assinatura',
                'quantity' => 1,
                'unit_price' => $price->getPriceAmount(),
                'currency_id' => 'BRL',
            ]],
            'payer' => ['email' => $user->getEmail()],
            'external_reference' => $externalReference,
            'notification_url' => $this->notificationUrl,
            'auto_recurring' => $autoRecurring,
            'back_urls' => [
                'success' => rtrim($this->frontendUrl, '/').'/dashboard/billing?status=success',
                'failure' => rtrim($this->frontendUrl, '/').'/dashboard/billing?status=failure',
                'pending' => rtrim($this->frontendUrl, '/').'/dashboard/billing?status=pending',
            ],
            'auto_return' => 'approved',
        ];
    }
}
