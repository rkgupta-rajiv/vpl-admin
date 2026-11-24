<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->prependToGroup('api', ForceJsonResponse::class);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Consistent JSON error envelope for the Flutter app.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
            if ($e instanceof AuthenticationException) {
                return response()->json(['success' => false, 'message' => 'Please log in again.'], 401);
            }
            if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
                return response()->json(['success' => false, 'message' => 'Not found.'], 404);
            }
            if ($e instanceof HttpExceptionInterface) {
                return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'Request failed.'], $e->getStatusCode());
            }
            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Something went wrong. Please try again.',
            ], 500);
        });
    })->create();
