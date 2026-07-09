<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\UserStatus;
use App\Infrastructure\Messenger\Message\SendVerificationEmailMessage;
use App\Infrastructure\Messenger\Message\SendWelcomeEmailMessage;
use App\Infrastructure\Persistence\Entity\EmailVerificationToken;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\EmailVerificationTokenRepository;
use App\Infrastructure\Persistence\Repository\UserRepository;
use App\Infrastructure\Security\MayLoveTokenService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class EmailVerificationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EmailVerificationTokenRepository $tokenRepository,
        private readonly MayLoveTokenService $tokenService,
        private readonly MessageBusInterface $messageBus,
        private readonly VerificationResendRateLimitService $resendRateLimit,
        private readonly string $frontendUrl,
        private readonly int $verificationHours,
        private readonly bool $emailVerificationEnabled,
    ) {
    }

    public function sendVerificationEmail(User $user): void
    {
        if (!$this->emailVerificationEnabled || $user->isEmailVerified()) {
            return;
        }

        $plainToken = $this->tokenService->generateRefreshToken();
        $tokenHash = $this->tokenService->hashToken($plainToken);
        $expiresAt = (new \DateTimeImmutable())->modify(sprintf('+%d hours', $this->verificationHours));

        $this->tokenRepository->invalidateUnusedForUser($user);

        $verificationToken = new EmailVerificationToken($user, $tokenHash, $expiresAt);
        $this->tokenRepository->save($verificationToken);

        $verificationUrl = rtrim($this->frontendUrl, '/').'/verify-email?token='.rawurlencode($plainToken);

        $this->messageBus->dispatch(new SendVerificationEmailMessage(
            messageId: Uuid::v7()->toRfc4122(),
            recipientEmail: $user->getEmail(),
            recipientName: $user->getName(),
            verificationUrl: $verificationUrl,
            validityHours: $this->verificationHours,
        ));
    }

    public function verifyByToken(string $token): User
    {
        $token = trim($token);
        if ($token === '') {
            throw new EmailVerificationException('Token de verificação inválido.');
        }

        $verificationToken = $this->tokenRepository->findValidByHash(
            $this->tokenService->hashToken($token),
        );

        if ($verificationToken === null) {
            throw new EmailVerificationException('Token de verificação inválido ou expirado.');
        }

        return $this->activateUser($verificationToken);
    }

    public function resendForUser(User $user): void
    {
        if (!$this->emailVerificationEnabled || $user->isEmailVerified()) {
            return;
        }

        $userId = (string) $user->getId();
        if (!$this->resendRateLimit->canResend($userId)) {
            throw new EmailVerificationException(
                'Limite de reenvios atingido. Tente novamente em até 1 hora.',
                'VERIFICATION_RESEND_LIMIT',
                429,
            );
        }

        $this->sendVerificationEmail($user);
        $this->resendRateLimit->registerResend($userId);
    }

    private function activateUser(EmailVerificationToken $verificationToken): User
    {
        $user = $verificationToken->getUser();

        if (!$user->isEmailVerified()) {
            $user->markEmailVerified();
            $this->userRepository->save($user);
        }

        $verificationToken->markAsUsed(new \DateTimeImmutable());
        $this->tokenRepository->save($verificationToken);

        $this->messageBus->dispatch(new SendWelcomeEmailMessage(
            messageId: Uuid::v7()->toRfc4122(),
            recipientEmail: $user->getEmail(),
            recipientName: $user->getName(),
            dashboardUrl: rtrim($this->frontendUrl, '/'),
        ));

        return $user;
    }
}
