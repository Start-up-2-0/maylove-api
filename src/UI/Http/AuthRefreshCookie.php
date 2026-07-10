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
        $sameSite = $secure ? Cookie::SAMESITE_NONE : Cookie::SAMESITE_LAX;

        $response->headers->setCookie(
            Cookie::create(self::NAME)
                ->withValue($refreshToken)
                ->withExpires($expiresAt)
                ->withPath('/api/v1/auth')
                ->withSecure($secure)
                ->withHttpOnly(true)
                ->withSameSite($sameSite),
        );
    }

    public static function clear(Response $response, bool $secure = false): void
    {
        $sameSite = $secure ? Cookie::SAMESITE_NONE : Cookie::SAMESITE_LAX;

        $response->headers->clearCookie(
            self::NAME,
            '/api/v1/auth',
            null,
            $secure,
            true,
            $sameSite,
        );
    }

    public static function resolve(Request $request, ?string $bodyToken): ?string
    {
        if ($bodyToken !== null && $bodyToken !== '') {
            return $bodyToken;
        }

        return $request->cookies->get(self::NAME);
    }
}
