<?php

use App\Exceptions\ApiException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The app runs behind a hosting proxy, which terminates HTTPS and
        // forwards the real client IP (used for URLs and rate limiting).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The API never renders HTML error pages.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Unknown routes, wrong HTTP verbs, throttling, etc. use the same
        // error envelope as the catalog's own exceptions.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            [$code, $message] = match ($status) {
                404 => ['not_found', 'The requested resource was not found.'],
                405 => ['method_not_allowed', 'This HTTP method is not supported for the requested resource.'],
                429 => ['too_many_requests', 'Too many requests. Please slow down.'],
                default => ['http_error', $e->getMessage() ?: 'The request could not be completed.'],
            };

            return ApiException::response($status, $code, $message)->withHeaders($e->getHeaders());
        });
    })->create();
