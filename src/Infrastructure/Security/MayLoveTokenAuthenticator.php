<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Auth\AuthSessionContext;
use App\Application\Auth\AuthSessionService;
use App\Domain\Auth\Exception\AuthException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class MayLoveTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly AuthSessionService $authSessionService,
        private readonly string $tokenHeaderName,
        private readonly int $slidingRenewalMinutes,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($this->isPublicPath($request->getPathInfo())) {
            return false;
        }

        return str_starts_with($request->getPathInfo(), '/api/v1');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $token = $request->headers->get($this->tokenHeaderName);
        if ($token === null || $token === '') {
            throw new AuthenticationException('Não autorizado.');
        }

        if ($request->headers->has('Authorization')) {
            throw new AuthenticationException('Use o header '.$this->tokenHeaderName.'.');
        }

        try {
            $context = new AuthSessionContext(
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
            );

            $authenticated = $this->authSessionService->getActiveSessionByAccessToken($token, $context);
            $session = $authenticated->session;

            $reference = $session->getLastRenewedAt() ?? $session->getLoginAt();
            if ($reference->modify(sprintf('+%d minutes', $this->slidingRenewalMinutes)) <= new \DateTimeImmutable()) {
                $this->authSessionService->renewSessionExpiration($session);
            }

            $userId = (string) $authenticated->user->getId();

            return new SelfValidatingPassport(
                new UserBadge($userId, fn () => new MayLoveUser($authenticated->user, (string) $session->getId())),
            );
        } catch (AuthException $exception) {
            throw new AuthenticationException($exception->getMessage(), 0, $exception);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->createUnauthorizedResponse($exception);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->createUnauthorizedResponse($authException);
    }

    private function createUnauthorizedResponse(?AuthenticationException $exception): JsonResponse
    {
        if ($exception !== null) {
            $previous = $exception->getPrevious();
            if ($previous instanceof AuthException) {
                return new JsonResponse([
                    'message' => $previous->getMessage(),
                    'code' => $previous->getErrorCode(),
                ], $previous->getStatusCode());
            }

            if ($exception->getMessage() !== '') {
                return new JsonResponse([
                    'message' => $exception->getMessage(),
                    'code' => 'UNAUTHORIZED',
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        return new JsonResponse([
            'message' => 'Não autorizado.',
            'code' => 'UNAUTHORIZED',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function isPublicPath(string $path): bool
    {
        return (bool) preg_match(
            '#^/api/v1/(health(?:/.*)?|auth/login|auth/refresh|auth/register|auth/verify-email|tribute-types|templates|presentations|billing/product|music-tracks|public/tributes|public/albums|public/maps|public/bouquets|albums/book-presentations)(/|$)#',
            $path,
        );
    }
}
