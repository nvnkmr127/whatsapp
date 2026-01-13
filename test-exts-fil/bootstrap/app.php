<?php

use App\Http\Middleware\CheckDatabaseVersion;
use App\Http\Middleware\SanitizeInputs;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Load admin routes
            Route::middleware(['web', 'validate'])
                ->prefix('admin')
                ->as('admin.')
                ->group([
                    __DIR__ . '/../routes/admin.php',
                    __DIR__ . '/../routes/system-settings.php',
                    __DIR__ . '/../routes/whatsmark-settings.php',
                ]);

            // Load utility routes
            Route::middleware('web')
                ->group(base_path('routes/utilities.php'));

            // Load API routes with api middleware
            Route::middleware(['api', 'api.token'])
                ->prefix('api')
                ->as('api.')
                ->group(__DIR__ . '/../routes/api.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SanitizeInputs::class);
        $middleware->append(SetLocale::class);
        $middleware->append(CheckDatabaseVersion::class);
        $middleware->alias([
            'api.token' => \App\Http\Middleware\ValidateApiToken::class,
            'validate'  => \Corbital\Installer\Http\Middleware\ValidateBackendRequest::class,
        ]);
        // exclude all webhook urls
        $middleware->validateCsrfTokens([
            'whatsapp/webhook', // Exclude this route from CSRF verification
            'admin/send-message',
        ]);
        $middleware->append(\Corbital\Installer\Http\Middleware\RedirectIfNotInstalled::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
