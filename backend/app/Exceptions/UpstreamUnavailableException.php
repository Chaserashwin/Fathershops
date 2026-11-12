<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * The catalog CDN could not be reached or answered with something unusable
 * (HTTP 502). Unlike a 404 this is reported, because it needs attention.
 */
class UpstreamUnavailableException extends ApiException
{
    public function __construct(string $path, string $reason, ?Throwable $previous = null)
    {
        parent::__construct("Catalog upstream request for [{$path}] failed: {$reason}", previous: $previous);
    }

    public function status(): int
    {
        return 502;
    }

    public function errorCode(): string
    {
        return 'upstream_unavailable';
    }

    public function render(): JsonResponse
    {
        // The internal reason stays in the log; clients get a stable message.
        return self::response(
            $this->status(),
            $this->errorCode(),
            'The catalog service is temporarily unavailable. Please try again shortly.',
        );
    }
}
