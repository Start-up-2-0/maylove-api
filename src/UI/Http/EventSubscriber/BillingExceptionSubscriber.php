<?php

declare(strict_types=1);

namespace App\UI\Http\EventSubscriber;

use App\Domain\Billing\Exception\BillingException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class BillingExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!$throwable instanceof BillingException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'message' => $throwable->getMessage(),
            'code' => $throwable->getErrorCode(),
        ], $throwable->getStatusCode()));
    }
}
