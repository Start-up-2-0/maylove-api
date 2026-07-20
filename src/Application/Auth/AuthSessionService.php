<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\Exception\InactiveUserException;
use App\Domain\Auth\Exception\InvalidTokenException;
use App\Domain\Auth\Exception\TokenExpiredException;
use App\Domain\Auth\Exception\UserBlockedException;
use App\Infrastructure\Persistence\Entity\AuthSession;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\AuthSessionRepository;
use App\Infrastructure\Security\MayLoveTokenService;
use Symfony\Component\Uid\Uuid;

final class AuthSessionService
{
    public function __construct(
        private readonly AuthSessionRepository $sessionRepository,
        private readonly MayLoveTokenService $tokenService,
        private readonly int $maxLoginAttempts,
        private readonly bool $validateIpOnToken,
        private readonly bool $validateUserAgentOnToken,
    ) {
    }

    public function createSessionWithTokens(User $user, AuthSessionContext $context): IssuedTokenPair
    {
        $now = new \DateTimeImmutable();
        $refreshToken = $this->tokenService->generateRefreshToken();

        $session = new AuthSession($user);
        $session->setRefreshTokenHash($this->tokenService->hashToken($refreshToken));
        $session->setExpiresAt($this->tokenService->getRefreshTokenExpiration($now));
        $session->setIp($context->ip);
        $session->setUserAgent($context->userAgent);
        $session->setMetadataJson([
            'userId' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'issuedAt' => $now->format(\DateTimeInterface::ATOM),
        ]);

        $accessToken = $this->tokenService->issueAccessToken($user, (string) $session->getId(), $now);
        $session->setAccessTokenHash($this->tokenService->hashToken($accessToken));
        $session->setAccessTokenExpiresAt($this->tokenService->getAccessTokenExpiration($now));
        $this->sessionRepository->save($session);

        return new IssuedTokenPair(
            $accessToken,
            $refreshToken,
            $session->getAccessTokenExpiresAt(),
            $session->getExpiresAt(),
            (string) $session->getId(),
        );
    }

    public function getActiveSessionByAccessToken(string $accessToken, AuthSessionContext $context): AuthenticatedSession
    {
        $metadata = $this->tokenService->validateMetadata($accessToken);
        if ($metadata === null) {
            throw new InvalidTokenException();
        }

        $now = new \DateTimeImmutable();
        if ($this->tokenService->isExpired($metadata, $now)) {
            throw new TokenExpiredException();
        }

        $session = $this->sessionRepository->findActiveByAccessTokenHash(
            $this->tokenService->hashToken($accessToken),
            Uuid::fromString($metadata->sessionId),
        );

        if ($session === null || (string) $session->getUser()->getId() !== $metadata->userId) {
            throw new InvalidTokenException();
        }

        $this->validateSessionContext($session, $context);

        $user = $session->getUser();
        if (!$user->canAuthenticate($this->maxLoginAttempts)) {
            if ($user->isBlocked($this->maxLoginAttempts)) {
                throw new UserBlockedException();
            }

            throw new InactiveUserException();
        }

        if ($user->isBlocked($this->maxLoginAttempts)) {
            throw new UserBlockedException();
        }

        return new AuthenticatedSession($session, $user);
    }

    public function getActiveSessionByRefreshToken(string $refreshToken): AuthSession
    {
        $session = $this->sessionRepository->findActiveByRefreshTokenHash(
            $this->tokenService->hashToken($refreshToken),
        );

        if ($session === null) {
            throw new InvalidTokenException();
        }

        if (!$session->isActive(new \DateTimeImmutable())) {
            throw new TokenExpiredException();
        }

        return $session;
    }

    public function findSessionById(Uuid $sessionId): ?AuthSession
    {
        return $this->sessionRepository->findById($sessionId);
    }

    public function revokeSession(AuthSession $session): void
    {
        $session->revoke(new \DateTimeImmutable());
        $this->sessionRepository->save($session);
    }

    public function rotateSession(AuthSession $currentSession, User $user, AuthSessionContext $context): IssuedTokenPair
    {
        $this->revokeSession($currentSession);

        return $this->createSessionWithTokens($user, $context);
    }

    public function renewSessionExpiration(AuthSession $session): void
    {
        $now = new \DateTimeImmutable();
        $session->renewExpiration($now, $this->tokenService->getRefreshTokenExpiration($now));
        $this->sessionRepository->save($session);
    }

    private function validateSessionContext(AuthSession $session, AuthSessionContext $context): void
    {
        if (
            $this->validateIpOnToken
            && $session->getIp() !== null
            && $context->ip !== null
            && $context->ip !== ''
            && $session->getIp() !== $context->ip
        ) {
            throw new InvalidTokenException();
        }

        if (
            $this->validateUserAgentOnToken
            && $session->getUserAgent() !== null
            && $context->userAgent !== null
            && $context->userAgent !== ''
            && $session->getUserAgent() !== $context->userAgent
        ) {
            throw new InvalidTokenException();
        }
    }
}
