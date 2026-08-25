<?php

use App\Exceptions\DomainException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (DomainException $e, Request $request) {
            if ($request->is('settings/approval-configurations') || $request->is('settings/approval-configurations/*')) {
                return response()->json([
                    'message' => 'Domain rule violation',
                    'error' => 'The action cannot be completed due to domain rules.',
                ], 422);
            }
        });
    })->create();
