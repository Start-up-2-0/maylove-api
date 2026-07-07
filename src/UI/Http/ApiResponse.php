<?php

declare(strict_types=1);

namespace App\UI\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    public static function success(string $message, mixed $data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        $payload = ['message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }

        return new JsonResponse($payload, $status);
    }

    public static function error(string $message, string $code, int $status): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'code' => $code,
        ], $status);
    }
}
