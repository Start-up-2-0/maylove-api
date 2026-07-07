<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class LoginRateLimitException extends AuthException
{
    public function __construct(string $message = 'Muitas tentativas de login. Tente novamente em instantes.')
    {
        parent::__construct($message, 'LOGIN_RATE_LIMIT', 429);
    }
}
