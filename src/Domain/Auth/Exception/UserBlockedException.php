<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class UserBlockedException extends AuthException
{
    public function __construct(string $message = 'Usuário temporariamente bloqueado.')
    {
        parent::__construct($message, 'USER_BLOCKED', 403);
    }
}
