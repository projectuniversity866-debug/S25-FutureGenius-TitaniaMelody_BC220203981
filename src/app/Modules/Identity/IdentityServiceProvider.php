<?php

namespace App\Modules\Identity;

use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Policies\UserPolicy;
use App\Modules\Identity\Support\PermissionCatalog;
use Illuminate\Support\Facades\Gate;
use App\Modules\ModuleServiceProvider;

/**
 * FR1 User Management — registration, login, roles, profiles and the
 * administrator's user management screen.
 */
class IdentityServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Identity';

    public function boot(): void
    {
        parent::boot();

        // Each permission slug is a Gate ability.
        foreach (array_keys(PermissionCatalog::all()) as $slug) {
            Gate::define($slug, fn (User $user) => $user->hasPermission($slug));
        }
    }

    protected function policies(): array
    {
        return [User::class => UserPolicy::class];
    }

    protected function morphMap(): array
    {
        return ['user' => User::class, 'role' => Role::class];
    }
}
