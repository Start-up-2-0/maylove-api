<?php

declare(strict_types=1);

namespace App\Application\User;

final class EmailVerificationException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'EMAIL_VERIFICATION_INVALID',
        private readonly int $statusCode = 400,
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
