<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The whole UI is Bootstrap 5, so paginate() links must be too.
        Paginator::useBootstrapFive();

        // Behind WAMP/Apache with HTTPS off this is a no-op; it keeps
        // generated URLs correct if the app is later served over HTTPS.
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
