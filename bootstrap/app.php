<?php

use App\Exceptions\DomainException;
use App\Http\Middleware\ActiveUserMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active-user' => \App\Http\Middleware\ActiveUserMiddleware::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'email-verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
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
