<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\Exception\InactiveUserException;
use App\Domain\Auth\Exception\InvalidCredentialsException;
use App\Domain\Auth\Exception\InvalidTokenException;
use App\Domain\Auth\Exception\LoginRateLimitException;
use App\Domain\Auth\Exception\UserBlockedException;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthSessionService $authSessionService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoginRateLimitService $loginRateLimitService,
        private readonly int $maxLoginAttempts,
        private readonly int $lockoutMinutes,
        private readonly bool $emailVerificationEnabled,
    ) {
    }

    public function login(string $email, string $password, AuthSessionContext $context): AuthLoginResult
    {
        $ip = $context->ip ?: 'unknown';
        if (!$this->loginRateLimitService->canAttempt($ip)) {
            throw new LoginRateLimitException();
        }

        $normalizedEmail = mb_strtolower(trim($email));
        $user = $this->userRepository->findByEmail($normalizedEmail);

        if ($user === null) {
            $this->loginRateLimitService->registerFailure($ip);

            throw new InvalidCredentialsException();
        }

        if ($user->getStatus()->value === 'suspended') {
            throw new InactiveUserException();
        }

        if ($user->isBlocked($this->maxLoginAttempts)) {
            throw new UserBlockedException();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            $user->registerFailedLoginAttempt();

            if ($user->getLoginAttempts() >= $this->maxLoginAttempts) {
                $user->applyTemporaryLockout($this->lockoutMinutes);
            }

            $this->userRepository->save($user);
            $this->loginRateLimitService->registerFailure($ip);

            throw new InvalidCredentialsException();
        }

        $user->resetLoginAttempts();
        $this->userRepository->save($user);

        $tokens = $this->authSessionService->createSessionWithTokens($user, $context);

        return new AuthLoginResult(
            $tokens,
            $user,
            $this->emailVerificationEnabled && !$user->isEmailVerified(),
        );
    }

    public function logout(string $accessToken, ?string $refreshToken = null): void
    {
        $auth = $this->authSessionService->getActiveSessionByAccessToken(
            $accessToken,
            new AuthSessionContext(null, null),
        );

        $session = $this->authSessionService->findSessionById($auth->session->getId());
        if ($session === null) {
            throw new InvalidTokenException('Sessão inválida.');
        }

        $this->authSessionService->revokeSession($session);

        // Revogar também pelo refresh token para invalidar tokens obtidos antes do logout
        if ($refreshToken !== null && $refreshToken !== '') {
            try {
                $refreshSession = $this->authSessionService->getActiveSessionByRefreshToken($refreshToken);
                if (!$refreshSession->getId()->equals($session->getId())) {
                    $this->authSessionService->revokeSession($refreshSession);
                }
            } catch (\Throwable) {
                // Refresh token já inválido ou inexistente — sem ação
            }
        }
    }

    public function refresh(string $refreshToken, AuthSessionContext $context): IssuedTokenPair
    {
        $session = $this->authSessionService->getActiveSessionByRefreshToken($refreshToken);
        $user = $session->getUser();

        if (!$user->canAuthenticate($this->maxLoginAttempts)) {
            $this->authSessionService->revokeSession($session);

            if ($user->isBlocked($this->maxLoginAttempts)) {
                throw new UserBlockedException();
            }

            throw new InactiveUserException();
        }

        return $this->authSessionService->rotateSession($session, $user, $context);
    }
}
