<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Base class for errors that are part of the API contract.
 *
 * Every API error is rendered with the same envelope:
 * {"error": {"status": 404, "code": "product_not_found", "message": "..."}}
 */
abstract class ApiException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;

    public function render(): JsonResponse
    {
        return self::response($this->status(), $this->errorCode(), $this->getMessage());
    }

    public static function response(int $status, string $code, string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'status' => $status,
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
