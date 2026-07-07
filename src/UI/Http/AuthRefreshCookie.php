<?php

declare(strict_types=1);

namespace App\UI\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthRefreshCookie
{
    public const NAME = 'maylove_refresh';

    public static function set(Response $response, string $refreshToken, \DateTimeImmutable $expiresAt, bool $secure): void
    {
        $response->headers->setCookie(
            Cookie::create(self::NAME)
                ->withValue($refreshToken)
                ->withExpires($expiresAt)
                ->withPath('/api/v1/auth')
                ->withSecure($secure)
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX),
        );
    }

    public static function clear(Response $response): void
    {
        $response->headers->clearCookie(self::NAME, '/api/v1/auth');
    }

    public static function resolve(Request $request, ?string $bodyToken): ?string
    {
        if ($bodyToken !== null && $bodyToken !== '') {
            return $bodyToken;
        }

        return $request->cookies->get(self::NAME);
    }
}
