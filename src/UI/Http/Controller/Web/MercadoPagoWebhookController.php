<?php

declare(strict_types=1);

namespace App\UI\Http\Controller\Web;

use App\Domain\Billing\Exception\BillingException;
use App\Infrastructure\Messenger\Message\ProcessMercadoPagoWebhookMessage;
use App\Infrastructure\Payment\MercadoPagoWebhookSignatureValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'webhook_')]
final class MercadoPagoWebhookController extends AbstractController
{
    public function __construct(
        private readonly MercadoPagoWebhookSignatureValidator $signatureValidator,
        private readonly MessageBusInterface $messageBus,
        private readonly bool $webhookSignatureRequired,
    ) {
    }

    #[Route('/webhooks/mercadopago', name: 'mercadopago', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('x-signature');
        $requestId = $request->headers->get('x-request-id');

        if ($this->webhookSignatureRequired && !$this->signatureValidator->validate($signature, $requestId, $payload)) {
            return new Response('Assinatura inválida.', Response::HTTP_UNAUTHORIZED);
        }

        /** @var array<string, mixed> $body */
        $body = json_decode($payload, true) ?? [];
        $eventType = is_string($body['type'] ?? null) ? (string) $body['type'] : 'unknown';
        $externalEventId = $requestId ?: sprintf('%s:%s', $eventType, $this->extractResourceId($body) ?? uniqid('', true));

        $this->messageBus->dispatch(new ProcessMercadoPagoWebhookMessage(
            $externalEventId,
            $eventType,
            $body,
        ));

        return new Response(null, Response::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function extractResourceId(array $body): ?string
    {
        if (isset($body['data']['id'])) {
            return is_scalar($body['data']['id']) ? (string) $body['data']['id'] : null;
        }

        return null;
    }
}
