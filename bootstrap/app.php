<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->redirectUsersTo(function ($request) {
            $user = $request->user();

            return ($user && method_exists($user, 'isEmployee') && $user->isEmployee())
                ? route('employee.dashboard')
                : route('dashboard');
        });

        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'employee' => \App\Http\Middleware\EnsureEmployeeRole::class,
            'not_employee' => \App\Http\Middleware\RedirectIfEmployee::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SessionTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
