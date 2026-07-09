<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\UserStatus;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EmailVerificationService $emailVerificationService,
    ) {
    }

    public function register(string $name, string $email, string $password): User
    {
        $normalizedEmail = mb_strtolower(trim($email));

        if ($this->userRepository->findByEmail($normalizedEmail) !== null) {
            throw new \InvalidArgumentException('E-mail já cadastrado.');
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($normalizedEmail);
        $user->setStatus(UserStatus::PendingVerification);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $password));

        $this->userRepository->save($user);

        try {
            $this->emailVerificationService->sendVerificationEmail($user);
        } catch (\Throwable) {
            // Cadastro não deve falhar se o envio assíncrono/e-mail estiver indisponível.
        }

        return $user;
    }
}
