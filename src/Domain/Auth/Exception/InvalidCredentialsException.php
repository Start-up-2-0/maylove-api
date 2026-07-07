<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class InvalidCredentialsException extends AuthException
{
    public function __construct(string $message = 'Credenciais inválidas.')
    {
        parent::__construct($message, 'INVALID_CREDENTIALS', 401);
    }
}
