<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware implementing prototype module #2 "Role-Based Access".
 *
 * Usage in routes/web.php:
 *     Route::get('/admin', ...)->middleware('role:admin');
 *     Route::post('/jobs',  ...)->middleware('role:admin,expert');
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasRole(...$roles)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
