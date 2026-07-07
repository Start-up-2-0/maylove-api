<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class InactiveUserException extends AuthException
{
    public function __construct(string $message = 'Conta inativa.')
    {
        parent::__construct($message, 'USER_INACTIVE', 403);
    }
}
