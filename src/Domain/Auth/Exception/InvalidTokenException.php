<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class InvalidTokenException extends AuthException
{
    public function __construct(string $message = 'Token inválido.')
    {
        parent::__construct($message, 'INVALID_TOKEN', 401);
    }
}
