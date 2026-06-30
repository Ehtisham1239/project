<?php

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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Webhook routes (api.php) are intentionally outside CSRF protection since
        // Meta/Twilio call them directly; signature verification happens in the
        // controllers themselves (see WhatsAppWebhookController::verifySignature).
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
