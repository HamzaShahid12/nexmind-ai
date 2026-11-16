<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAdmin;
use App\Services\AI\AIException;
use App\Services\AI\ToolLimitException;
use App\Services\Credit\InsufficientCreditsException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cookie/session based Sanctum authentication for the Vue SPA (CSRF + stateful domains).
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn () => null);   // API: always answer 401 JSON, never redirect
        $middleware->alias([
            'active' => EnsureAccountActive::class,
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(fn (InsufficientCreditsException $e, Request $r) => response()->json([
            'message' => $e->getMessage(), 'required' => $e->required, 'available' => $e->available,
        ], 402));

        $exceptions->render(fn (AIException $e, Request $r) => response()->json([
            'message' => $e->getMessage(), 'code' => $e->errorCode,
        ], $e->status));

        $exceptions->render(fn (ToolLimitException $e, Request $r) => response()->json(['message' => $e->getMessage()], $e->status));

        // Safe, generic JSON for everything else under /api. Details are logged, never returned.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') || $e instanceof ValidationException) {
                return null;   // validation errors keep Laravel's 422 format
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'You need to log in to continue.'], 401);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json(['message' => 'The requested resource was not found.'], 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $messages = [
                    403 => 'You do not have permission to perform this action.',
                    405 => 'This method is not allowed.',
                    419 => 'Your session expired. Please refresh the page and try again.',
                    429 => 'Too many requests. Please slow down and try again shortly.',
                ];

                return response()->json(['message' => $messages[$status] ?? 'The request could not be completed.'], $status);
            }

            return response()->json(['message' => 'Something went wrong on our side. Please try again later.'], 500);
        });
    })->create();
