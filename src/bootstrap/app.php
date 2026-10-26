<?php

use App\Modules\Identity\Http\Middleware\EnsureUserIsActive;
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
        // Access is checked with permissions (can:<slug>), see PermissionCatalog.
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);

        // Every authenticated web request is checked for a deactivated account.
        $middleware->appendToGroup('web', EnsureUserIsActive::class);

        // Accessibility display preferences are not secret, and the page's
        // script must be able to change them instantly (roadmap 2.7).
        $middleware->encryptCookies(except: ['aq_a11y']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
