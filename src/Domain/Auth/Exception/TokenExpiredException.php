<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class TokenExpiredException extends AuthException
{
    public function __construct(string $message = 'Token expirado.')
    {
        parent::__construct($message, 'TOKEN_EXPIRED', 401);
    }
}
