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
    ->withMiddleware(function (Middleware $middleware) {
        // Temporarily disable FOH middleware for debugging
        $middleware->web(append: [
            // \App\Http\Middleware\EnsureFohAccess::class,
            \App\Http\Middleware\HandlePermissionExceptions::class,
            \App\Http\Middleware\SetTenantTimezone::class,
            \App\Http\Middleware\SetUserLanguage::class,
        ]);
        
        // Register middleware aliases
        $middleware->alias([
            'public.language' => \App\Http\Middleware\SetPublicLanguage::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();