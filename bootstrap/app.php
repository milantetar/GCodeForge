<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['active' => EnsureAccountIsActive::class]);
        $middleware->web(append: [
            EnsureAccountIsActive::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response): Response {
            if (! request()->is('api/*') || $response->getStatusCode() < 400) {
                return $response;
            }
            $payload = json_decode($response->getContent() ?: '{}', true);
            $status = $response->getStatusCode();
            $message = $status >= 500 ? 'An unexpected error occurred. Please try again.'
                : (is_array($payload) && is_string($payload['message'] ?? null) ? $payload['message'] : (Response::$statusTexts[$status] ?? 'Request failed.'));
            $result = response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $status < 500 && is_array($payload) ? ($payload['errors'] ?? null) : null,
            ], $status);
            foreach (['Retry-After', 'WWW-Authenticate', 'Allow'] as $header) {
                if ($response->headers->has($header)) {
                    $result->headers->set($header, $response->headers->get($header));
                }
            }

            return $result;
        });
    })->create();
