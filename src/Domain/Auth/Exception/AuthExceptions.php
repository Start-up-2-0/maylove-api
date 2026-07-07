<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class UnauthorizedException extends AuthException
{
    public function __construct(string $message = 'Não autorizado.')
    {
        parent::__construct($message, 'UNAUTHORIZED', 401);
    }
}

final class InvalidTokenException extends AuthException
{
    public function __construct(string $message = 'Token inválido.')
    {
        parent::__construct($message, 'INVALID_TOKEN', 401);
    }
}

final class TokenExpiredException extends AuthException
{
    public function __construct(string $message = 'Token expirado.')
    {
        parent::__construct($message, 'TOKEN_EXPIRED', 401);
    }
}

final class InvalidCredentialsException extends AuthException
{
    public function __construct(string $message = 'Credenciais inválidas.')
    {
        parent::__construct($message, 'INVALID_CREDENTIALS', 401);
    }
}

final class UserBlockedException extends AuthException
{
    public function __construct(string $message = 'Usuário temporariamente bloqueado.')
    {
        parent::__construct($message, 'USER_BLOCKED', 403);
    }
}

final class InactiveUserException extends AuthException
{
    public function __construct(string $message = 'Conta inativa.')
    {
        parent::__construct($message, 'USER_INACTIVE', 403);
    }
}

final class LoginRateLimitException extends AuthException
{
    public function __construct(string $message = 'Muitas tentativas de login. Tente novamente em instantes.')
    {
        parent::__construct($message, 'LOGIN_RATE_LIMIT', 429);
    }
}
