<?php

namespace App\Modules;

use App\Modules\Platform\Support\PersonalData;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Base class for every AQDesk module (see docs/architecture/ARCHITECTURE.md §7).
 *
 * A module lives in app/Modules/<Name>/ and is switched on by listing its
 * provider in bootstrap/providers.php. Whatever the module folder contains
 * is wired up automatically:
 *
 *   routes/web.php          loaded inside the "web" middleware group
 *   resources/views         available as view('<module-slug>::name')
 *   Database/Migrations     run by `php artisan migrate`
 *
 * Policies, polymorphic aliases and relations the module attaches to models
 * from other modules are declared through policies(), morphMap() and
 * relations().
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Folder name under app/Modules, e.g. "Community". */
    protected string $module;

    public function register(): void
    {
        $this->app->singletonIf(PersonalData::class);
    }

    public function boot(): void
    {
        $path = app_path('Modules/'.$this->module);

        if (! $this->app->routesAreCached() && is_file($path.'/routes/web.php')) {
            Route::middleware('web')->group($path.'/routes/web.php');
        }

        if (is_dir($path.'/resources/views')) {
            $this->loadViewsFrom($path.'/resources/views', $this->slug());
        }

        if (is_dir($path.'/Database/Migrations')) {
            $this->loadMigrationsFrom($path.'/Database/Migrations');
        }

        foreach ($this->policies() as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach ($this->listeners() as $event => $listeners) {
            foreach ((array) $listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        Relation::morphMap($this->morphMap());

        foreach ($this->relations() as $model => $relations) {
            foreach ($relations as $name => $resolver) {
                $model::resolveRelationUsing($name, $resolver);
            }
        }

        $registry = $this->app->make(PersonalData::class);
        foreach ($this->personalData() as $section => $exporter) {
            $registry->export($section, $exporter);
        }
        foreach ($this->personalDataErasers() as $eraser) {
            $registry->erase($eraser);
        }
    }

    /**
     * What this module stores about a member, for "Download my data"
     * (roadmap 2.6): section name => fn (User $user) => array.
     *
     * @return array<string, \Closure>
     */
    protected function personalData(): array
    {
        return [];
    }

    /**
     * How to erase this module's personal data when a member deletes their
     * account. Public contributions may stay, shown as "Deleted user".
     *
     * @return array<int, \Closure>
     */
    protected function personalDataErasers(): array
    {
        return [];
    }

    /** View namespace, e.g. "events-media" for EventsMedia. */
    public function slug(): string
    {
        return Str::kebab($this->module);
    }

    /**
     * Authorisation rules for the module's models. Registered explicitly so
     * the rule behind every $this->authorize() and @can is easy to find.
     *
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [];
    }

    /**
     * Domain events this module reacts to, e.g. sending a notification when
     * another module reports that something happened.
     *
     * @return array<class-string, class-string|array<int, class-string>>
     */
    protected function listeners(): array
    {
        return [];
    }

    /**
     * Short, stable names stored in *_type columns instead of class names,
     * so models can move between namespaces without breaking stored data.
     *
     * @return array<string, class-string>
     */
    protected function morphMap(): array
    {
        return [];
    }

    /**
     * Relations this module adds to models it does not own. This keeps the
     * dependency one-way: the feature module knows about User, User does not
     * know about the feature module.
     *
     * @return array<class-string, array<string, \Closure>>
     */
    protected function relations(): array
    {
        return [];
    }
}
