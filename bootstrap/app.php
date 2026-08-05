<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\ApiErrorResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $exception instanceof ApiException => ApiErrorResponse::make(
                    $exception->errorCode,
                    $exception->getMessage(),
                    $exception->status,
                ),
                $exception instanceof ValidationException => ApiErrorResponse::make(
                    'validation_failed',
                    'The request data is invalid.',
                    $exception->status,
                    $exception->errors(),
                ),
                $exception instanceof AuthenticationException => ApiErrorResponse::make(
                    'unauthenticated',
                    'Authentication is required.',
                    401,
                ),
                $exception instanceof AccessDeniedHttpException => ApiErrorResponse::make(
                    'forbidden',
                    'You are not authorized to perform this action.',
                    403,
                ),
                $exception instanceof NotFoundHttpException => ApiErrorResponse::make(
                    'resource_not_found',
                    'The requested resource was not found.',
                    404,
                ),
                $exception instanceof MethodNotAllowedHttpException => ApiErrorResponse::make(
                    'method_not_allowed',
                    'The HTTP method is not supported for this endpoint.',
                    405,
                ),
                config('app.debug') => null,
                default => ApiErrorResponse::make('server_error', 'An unexpected error occurred.', 500),
            };
        });
    })->create();
