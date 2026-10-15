<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * RFC 9457 `application/problem+json` response matching `#/components/schemas/Error`.
 */
final class Problem
{
    /**
     * @param array<string, mixed> $extra
     * @param array<string, string> $headers
     */
    public static function response(int $status, ?string $detail = null, array $extra = [], array $headers = []): JsonResponse
    {
        $body = [
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
        ];
        if (null !== $detail && '' !== $detail) {
            $body['detail'] = $detail;
        }

        return new JsonResponse($body + $extra, $status, ['Content-Type' => 'application/problem+json'] + $headers);
    }
}
