<?php

declare(strict_types=1);

namespace App\UI\Http\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $exposeErrors,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -128],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->getResponse() !== null) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $throwable = $event->getThrowable();
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
        }

        $payload = [
            'message' => $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Erro interno.',
            'code' => 'INTERNAL_ERROR',
        ];

        if ($this->exposeErrors) {
            $payload['detail'] = $throwable->getMessage();
            $payload['exception'] = $throwable::class;

            $previous = $throwable->getPrevious();
            if ($previous !== null) {
                $payload['previous'] = [
                    'message' => $previous->getMessage(),
                    'exception' => $previous::class,
                ];
            }
        }

        $event->setResponse(new JsonResponse($payload, $status));
    }
}
