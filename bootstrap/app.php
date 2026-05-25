<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\TouchLastSaveAction::class,
        ]);

        //
        // Voeg hier de alias voor 'beheerder' toe
        $middleware->alias([
            'beheerder' => \App\Http\Middleware\CheckBeheerder::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Je sessie is verlopen. Ververs de pagina en probeer opnieuw.',
                ], 419);
            }

            return redirect()
                ->back()
                ->with('error', 'Je sessie was verlopen. Controleer je invoer en probeer opnieuw.');
        });
    })->create();
