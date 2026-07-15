<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Domain\Billing\PaymentStatus;
use App\Domain\Billing\ProductBillingMode;
use App\Domain\Billing\SubscriptionStatus;
use App\Domain\Tribute\BillingModeUsed;
use App\Domain\Shared\UuidValue;
use App\Infrastructure\Payment\MercadoPagoClient;
use App\Infrastructure\Persistence\Entity\Billing\Payment;
use App\Infrastructure\Persistence\Entity\Billing\PaymentEvent;
use App\Infrastructure\Persistence\Repository\Billing\OrderRepository;
use App\Infrastructure\Persistence\Repository\Billing\PaymentEventRepository;
use App\Infrastructure\Persistence\Repository\Billing\PaymentRepository;
use App\Infrastructure\Persistence\Repository\Billing\UserSubscriptionRepository;

final class MercadoPagoWebhookProcessor
{
    public function __construct(
        private readonly PaymentEventRepository $paymentEventRepository,
        private readonly MercadoPagoClient $mercadoPagoClient,
        private readonly OrderRepository $orderRepository,
        private readonly UserSubscriptionRepository $subscriptionRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly TributePublicationService $tributePublicationService,
        private readonly AlbumPublicationService $albumPublicationService,
        private readonly SubscriptionCapabilityService $subscriptionCapability,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function process(string $externalEventId, string $eventType, array $payload): void
    {
        if ($this->paymentEventRepository->existsByExternalEventId($externalEventId)) {
            return;
        }

        $event = new PaymentEvent($externalEventId, $eventType, $payload);
        $this->paymentEventRepository->save($event);

        $topic = is_string($payload['type'] ?? null) ? (string) $payload['type'] : $eventType;
        $action = is_string($payload['action'] ?? null) ? (string) $payload['action'] : '';

        if ($topic === 'payment' || str_starts_with($action, 'payment.')) {
            $this->processPaymentNotification($payload, $event);
            return;
        }

        if ($topic === 'subscription_preapproval' || str_contains($action, 'subscription')) {
            $this->processSubscriptionNotification($payload, $event);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function processPaymentNotification(array $payload, PaymentEvent $event): void
    {
        $paymentId = $this->extractResourceId($payload);
        if ($paymentId === null) {
            $event->markProcessed();
            $this->paymentEventRepository->save($event);

            return;
        }

        if ($this->paymentRepository->findByMpPaymentId($paymentId) !== null) {
            $event->markProcessed();
            $this->paymentEventRepository->save($event);

            return;
        }

        $paymentData = $this->mercadoPagoClient->getPayment($paymentId);
        $status = is_string($paymentData['status'] ?? null) ? (string) $paymentData['status'] : '';
        $externalReference = is_string($paymentData['external_reference'] ?? null)
            ? (string) $paymentData['external_reference']
            : '';

        if ($status !== 'approved' || $externalReference === '') {
            $event->markProcessed();
            $this->paymentEventRepository->save($event);

            return;
        }

        $amountCents = (int) round(((float) ($paymentData['transaction_amount'] ?? 0)) * 100);
        $paymentStatus = PaymentStatus::Approved;

        if (str_starts_with($externalReference, 'order:')) {
            $this->processOrderPayment($externalReference, $paymentId, $paymentStatus, $amountCents, $paymentData);
        } elseif (str_starts_with($externalReference, 'subscription:')) {
            $this->processSubscriptionPayment($externalReference, $paymentId, $paymentStatus, $amountCents, $paymentData);
        }

        $event->markProcessed();
        $this->paymentEventRepository->save($event);
    }

    /**
     * @param array<string, mixed> $paymentData
     */
    private function processOrderPayment(
        string $externalReference,
        string $mpPaymentId,
        PaymentStatus $status,
        int $amountCents,
        array $paymentData,
    ): void {
        $order = $this->orderRepository->findByExternalReference($externalReference);
        if ($order === null) {
            return;
        }

        $payment = new Payment($mpPaymentId, $status, $amountCents, $order, null, $paymentData);
        $this->paymentRepository->save($payment);

        $order->markPaid();
        $this->orderRepository->save($order);

        $tribute = $order->getTribute();
        if ($tribute !== null) {
            $this->tributePublicationService->publishFromPayment(
                $tribute,
                BillingModeUsed::PerTribute,
            );

            return;
        }

        $album = $order->getAlbum();
        if ($album !== null) {
            $this->albumPublicationService->publishFromPayment($album);
        }
    }

    /**
     * @param array<string, mixed> $paymentData
     */
    private function processSubscriptionPayment(
        string $externalReference,
        string $mpPaymentId,
        PaymentStatus $status,
        int $amountCents,
        array $paymentData,
    ): void {
        $subscriptionId = substr($externalReference, strlen('subscription:'));
        if (!UuidValue::isValid($subscriptionId)) {
            return;
        }

        $subscription = $this->subscriptionRepository->find(UuidValue::fromString($subscriptionId));
        if ($subscription === null) {
            return;
        }

        $payment = new Payment($mpPaymentId, $status, $amountCents, null, $subscription, $paymentData);
        $this->paymentRepository->save($payment);

        $mode = $subscription->getProductPrice()->getBillingMode();
        $periodStart = new \DateTimeImmutable();
        $periodEnd = match ($mode) {
            ProductBillingMode::SubscriptionMonthly => $periodStart->modify('+1 month'),
            ProductBillingMode::SubscriptionYearly => $periodStart->modify('+1 year'),
            default => $periodStart->modify('+1 month'),
        };

        $mpSubscriptionId = is_string($paymentData['preapproval_id'] ?? null)
            ? (string) $paymentData['preapproval_id']
            : null;

        $subscription->activate($periodStart, $periodEnd, $mpSubscriptionId);
        $this->subscriptionRepository->save($subscription);
        $this->subscriptionCapability->invalidateCache((string) $subscription->getUser()->getId());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function processSubscriptionNotification(array $payload, PaymentEvent $event): void
    {
        $resourceId = $this->extractResourceId($payload);
        if ($resourceId === null) {
            $event->markProcessed();
            $this->paymentEventRepository->save($event);

            return;
        }

        $preapproval = $this->mercadoPagoClient->getPreapproval($resourceId);
        $status = is_string($preapproval['status'] ?? null) ? (string) $preapproval['status'] : '';
        $externalReference = is_string($preapproval['external_reference'] ?? null)
            ? (string) $preapproval['external_reference']
            : '';

        if (!str_starts_with($externalReference, 'subscription:')) {
            $event->markProcessed();
            $this->paymentEventRepository->save($event);

            return;
        }

        $subscriptionId = substr($externalReference, strlen('subscription:'));
        if (!UuidValue::isValid($subscriptionId)) {
            $event->markProcessed();
            $this->paymentEventRepository->save($event);

            return;
        }

        $subscription = $this->subscriptionRepository->find(UuidValue::fromString($subscriptionId));
        if ($subscription === null) {
            $event->markProcessed();
            $this->paymentEventRepository->save($event);

            return;
        }

        if ($status === 'authorized' || $status === 'active') {
            $periodStart = new \DateTimeImmutable();
            $mode = $subscription->getProductPrice()->getBillingMode();
            $periodEnd = match ($mode) {
                ProductBillingMode::SubscriptionMonthly => $periodStart->modify('+1 month'),
                ProductBillingMode::SubscriptionYearly => $periodStart->modify('+1 year'),
                default => $periodStart->modify('+1 month'),
            };
            $subscription->activate($periodStart, $periodEnd, $resourceId);
            $this->subscriptionRepository->save($subscription);
            $this->subscriptionCapability->invalidateCache((string) $subscription->getUser()->getId());
        } elseif ($status === 'cancelled') {
            $subscription->markCancelled();
            $this->subscriptionRepository->save($subscription);
            $this->subscriptionCapability->invalidateCache((string) $subscription->getUser()->getId());
        } elseif ($status === 'paused' || $status === 'pending') {
            $subscription->markPastDue();
            $this->subscriptionRepository->save($subscription);
            $this->subscriptionCapability->invalidateCache((string) $subscription->getUser()->getId());
        }

        $event->markProcessed();
        $this->paymentEventRepository->save($event);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractResourceId(array $payload): ?string
    {
        if (isset($payload['data']) && is_array($payload['data']) && isset($payload['data']['id'])) {
            $id = $payload['data']['id'];
            if (is_string($id) || is_int($id)) {
                return (string) $id;
            }
        }

        return null;
    }
}
