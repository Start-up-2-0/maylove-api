<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Application\Auth\AuthService;
use App\Application\Auth\AuthSessionContext;
use App\Application\Billing\SubscriptionCapabilityService;
use App\Application\User\EmailVerificationService;
use App\Application\User\RegisterUserService;
use App\Application\User\EmailVerificationException;
use App\Domain\Auth\Exception\AuthException;
use App\Infrastructure\Persistence\Entity\User;
use App\Infrastructure\Security\MayLoveUser;
use App\UI\Http\ApiResponse;
use App\UI\Http\AuthRefreshCookie;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/auth', name: 'auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RegisterUserService $registerUserService,
        private readonly EmailVerificationService $emailVerificationService,
        private readonly SubscriptionCapabilityService $subscriptionCapability,
        private readonly ValidatorInterface $validator,
        private readonly bool $cookieSecure,
        private readonly string $tokenHeaderName,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        /** @var array{name?: string, email?: string, password?: string, accept_terms?: bool} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        $violations = $this->validator->validate($payload, new Assert\Collection([
            'fields' => [
                'name' => [new Assert\NotBlank(), new Assert\Length(min: 2, max: 120)],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'password' => [new Assert\NotBlank(), new Assert\Length(min: 8, max: 128)],
                'accept_terms' => [new Assert\IsTrue(message: 'É necessário aceitar os termos.')],
            ],
            'allowExtraFields' => true,
        ]));

        if (count($violations) > 0) {
            return ApiResponse::error('Dados inválidos.', 'VALIDATION_ERROR', Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->registerUserService->register(
                (string) $payload['name'],
                (string) $payload['email'],
                (string) $payload['password'],
            );
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 'EMAIL_ALREADY_EXISTS', Response::HTTP_CONFLICT);
        } catch (UniqueConstraintViolationException) {
            return ApiResponse::error('E-mail já cadastrado.', 'EMAIL_ALREADY_EXISTS', Response::HTTP_CONFLICT);
        } catch (\Throwable) {
            return ApiResponse::error('Não foi possível criar a conta.', 'REGISTER_FAILED', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $message = $user->isEmailVerified()
            ? 'Cadastro realizado com sucesso. Você já pode entrar.'
            : 'Cadastro realizado com sucesso. Verifique seu e-mail para confirmar a conta.';

        return ApiResponse::success($message, [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'email_verified' => $user->isEmailVerified(),
            'status' => $user->getStatus()->value,
        ], Response::HTTP_CREATED);
    }

    #[Route('/verify-email', name: 'verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = (string) $request->query->get('token', '');

        try {
            $user = $this->emailVerificationService->verifyByToken($token);
        } catch (EmailVerificationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }

        return ApiResponse::success('E-mail confirmado com sucesso. Você já pode usar o MayLove.', [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'email_verified' => $user->isEmailVerified(),
            'status' => $user->getStatus()->value,
        ]);
    }

    #[Route('/resend-verification', name: 'resend_verification', methods: ['POST'])]
    public function resendVerification(#[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->emailVerificationService->resendForUser($user->getUser());
        } catch (EmailVerificationException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }

        return ApiResponse::success('Se o e-mail estiver pendente de confirmação, enviaremos um novo link.');
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        /** @var array{email?: string, password?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];

        try {
            $result = $this->authService->login(
                (string) ($payload['email'] ?? ''),
                (string) ($payload['password'] ?? ''),
                $this->buildSessionContext($request),
            );
        } catch (AuthException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }

        $response = ApiResponse::success('Login realizado com sucesso.', [
            'token' => $result->tokens->accessToken,
            'refreshToken' => '',
            'expiresAt' => $result->tokens->accessTokenExpiresAt->format(\DateTimeInterface::ATOM),
            'refreshExpiresAt' => $result->tokens->refreshTokenExpiresAt->format(\DateTimeInterface::ATOM),
            'user' => $this->serializeUser($result->user),
            'requiresEmailConfirmation' => $result->requiresEmailConfirmation,
        ]);

        AuthRefreshCookie::set(
            $response,
            $result->tokens->refreshToken,
            $result->tokens->refreshTokenExpiresAt,
            $this->cookieSecure,
        );

        return $response;
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        /** @var array{refreshToken?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $refreshToken = AuthRefreshCookie::resolve($request, $payload['refreshToken'] ?? null);

        if ($refreshToken === null || $refreshToken === '') {
            return ApiResponse::error('Refresh token ausente.', 'INVALID_TOKEN', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $tokens = $this->authService->refresh($refreshToken, $this->buildSessionContext($request));
        } catch (AuthException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }

        $response = ApiResponse::success('Token renovado com sucesso.', [
            'token' => $tokens->accessToken,
            'refreshToken' => '',
            'expiresAt' => $tokens->accessTokenExpiresAt->format(\DateTimeInterface::ATOM),
            'refreshExpiresAt' => $tokens->refreshTokenExpiresAt->format(\DateTimeInterface::ATOM),
        ]);

        AuthRefreshCookie::set(
            $response,
            $tokens->refreshToken,
            $tokens->refreshTokenExpiresAt,
            $this->cookieSecure,
        );

        return $response;
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->headers->get($this->tokenHeaderName);
        if ($token === null || $token === '') {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->authService->logout($token);
        } catch (AuthException $exception) {
            return ApiResponse::error($exception->getMessage(), $exception->getErrorCode(), $exception->getStatusCode());
        }

        $response = ApiResponse::success('Logout realizado com sucesso.');
        AuthRefreshCookie::clear($response);

        return $response;
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?MayLoveUser $user): JsonResponse
    {
        if ($user === null) {
            return ApiResponse::error('Não autorizado.', 'UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
        }

        return ApiResponse::success('OK', $this->serializeMe($user->getUser()));
    }

    private function buildSessionContext(Request $request): AuthSessionContext
    {
        return new AuthSessionContext(
            $request->getClientIp(),
            $request->headers->get('User-Agent'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => (string) $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'email_verified' => $user->isEmailVerified(),
            'status' => $user->getStatus()->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMe(User $user): array
    {
        return [
            'id' => (string) $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'email_verified' => $user->isEmailVerified(),
            'status' => $user->getStatus()->value,
            'subscription' => [
                'has_subscription' => $this->subscriptionCapability->hasActiveSubscription((string) $user->getId()),
                'billing_mode' => $this->subscriptionCapability->getBillingMode((string) $user->getId())?->value,
            ],
        ];
    }
}
