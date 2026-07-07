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
