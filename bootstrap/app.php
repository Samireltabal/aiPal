<?php

use App\Http\Middleware\EnsurePersonaExists;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'persona' => EnsurePersonaExists::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/webhooks/telegram',
            '/webhooks/whatsapp',
            '/webhooks/workflow/*',
            '/webhooks/email/inbound',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
