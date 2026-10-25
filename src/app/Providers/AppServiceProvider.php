<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
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
        // MySQL/MariaDB versions here cap index keys at 767-1000 bytes;
        // utf8mb4 VARCHAR(255) unique indexes exceed that. Cap the default.
        Schema::defaultStringLength(191);

        // Polymorphic columns store the short aliases each module declares
        // ("question", "answer", "user"), never class names. Fail loudly if a
        // model is used polymorphically without one.
        Relation::requireMorphMap();

        // The whole UI is Bootstrap 5, so paginate() links must be too.
        Paginator::useBootstrapFive();

        // Behind WAMP/Apache with HTTPS off this is a no-op; it keeps
        // generated URLs correct if the app is later served over HTTPS.
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
