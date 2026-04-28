<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (for reverse proxy / load balancer)
        $middleware->trustProxies(at: '*');

        // API rate limiting
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
        ]);

        // Register middleware aliases
        $middleware->alias([
            'seller' => \App\Http\Middleware\EnsureSellerRole::class,
            'webhook.verify' => \App\Http\Middleware\VerifyWebhookSignature::class,
            'cache.headers' => \App\Http\Middleware\CacheControl::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle ModelNotFoundException for API requests
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Kayit bulunamadi.',
                    'error' => 'not_found',
                ], 404);
            }
        });

        // Handle NotFoundHttpException for API requests
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Sayfa bulunamadi.',
                    'error' => 'not_found',
                ], 404);
            }
        });

        // Handle ValidationException for API requests
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Dogrulama hatasi.',
                    'error' => 'validation_error',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle AuthenticationException for API requests
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Kimlik dogrulanmadi. Lutfen giris yapin.',
                    'error' => 'unauthenticated',
                ], 401);
            }
        });
    })->create();
