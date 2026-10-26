<?php

namespace App\Modules\Identity;

use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Policies\UserPolicy;
use App\Modules\Identity\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    /** Your account, profile and linked sign-in providers (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'account' => fn (User $user) => [
                'name' => $user->name, 'email' => $user->email, 'role' => $user->roleName(),
                'joined' => $user->created_at?->toIso8601String(), 'email_confirmed' => $user->email_verified_at?->toIso8601String(),
                'settings' => array_merge(User::DEFAULT_SETTINGS, $user->settings ?? []),
            ],
            'profile' => fn (User $user) => $user->profile?->only([
                'headline', 'bio', 'skills', 'organization', 'location', 'website', 'linkedin', 'github',
            ]),
            'linked_sign_in' => fn (User $user) => $user->socialAccounts()->get(['provider', 'email', 'created_at'])->toArray(),
        ];
    }

    /**
     * Account deletion keeps the row (so public posts can say "Deleted user")
     * but removes everything that identifies the person, and blocks sign-in.
     */
    protected function personalDataErasers(): array
    {
        return [function (User $user) {
            if ($path = $user->profile?->avatar_path) {
                Storage::disk('public')->delete($path);
            }
            $user->profile?->update(array_fill_keys([
                'avatar_path', 'headline', 'bio', 'skills', 'organization', 'location', 'website', 'linkedin', 'github',
            ], null));
            $user->socialAccounts()->delete();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $user->forceFill([
                'name' => 'Deleted user', 'email' => 'deleted-'.$user->id.'@aqdesk.invalid',
                'password' => Str::random(40), 'remember_token' => null, 'email_verified_at' => null,
                'settings' => null, 'is_active' => false,
            ])->save();
        }];
    }
}
