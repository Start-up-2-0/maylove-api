<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Billing;

use App\Application\Billing\BillingProductQueryService;
use App\Application\Billing\SubscriptionService;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/billing', name: 'billing_')]
final class BillingController extends AbstractController
{
    public function __construct(
        private readonly BillingProductQueryService $productQueryService,
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    #[Route('/product', name: 'product', methods: ['GET'])]
    public function product(): JsonResponse
    {
        return ApiResponse::success('OK', $this->productQueryService->getPremiumProduct());
    }

    #[Route('/subscription', name: 'subscription', methods: ['GET'])]
    public function subscription(#[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success('OK', $this->subscriptionService->getSubscription($user->getUser()));
    }

    #[Route('/subscription/checkout', name: 'subscription_checkout', methods: ['POST'])]
    public function subscriptionCheckout(Request $request, #[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array{billing_mode?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $result = $this->subscriptionService->startCheckout(
            $user->getUser(),
            (string) ($payload['billing_mode'] ?? ''),
        );

        return ApiResponse::success('Checkout iniciado.', $result);
    }

    #[Route('/subscription/cancel', name: 'subscription_cancel', methods: ['POST'])]
    public function subscriptionCancel(#[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        $result = $this->subscriptionService->cancel($user->getUser());

        return ApiResponse::success('Assinatura será cancelada ao fim do período.', $result);
    }
}
