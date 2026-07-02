<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'expert.verified' => \App\Http\Middleware\EnsureExpertVerified::class,
        ]);

        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        // API-only: jangan redirect ke route 'login', langsung throw 401
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                // Ensure all API errors return JSON
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi gagal.',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException || $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resource tidak ditemukan.',
                    ], 404);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated.',
                    ], 401);
                }

                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException || $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aksi ini tidak sah (Unauthorized).',
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan pada server.',
                    'error'   => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
                ], 500);
            }
        });
    })->create();
