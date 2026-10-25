<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Models\Profile;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    /**
     * Roles a visitor may choose when signing up. Only an administrator can
     * make somebody an Administrator.
     */
    public const SELF_SERVICE_ROLES = [Role::MEMBER, Role::EXPERT];

    /** @return Collection<int, Role> */
    public function selfServiceRoles(): Collection
    {
        return Role::whereIn('slug', self::SELF_SERVICE_ROLES)->get();
    }

    /**
     * Create the account and its (mostly empty) profile together, so no user
     * ever exists without a profile row.
     *
     * @param  array{name: string, email: string, password: string, role_slug: string, headline?: ?string}  $data
     */
    public function register(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $role = Role::where('slug', $data['role_slug'])->firstOrFail();

            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => $data['password'],   // hashed by the model cast
                'role_id'   => $role->id,
                'is_active' => true,
            ]);

            Profile::create([
                'user_id'  => $user->id,
                'headline' => $data['headline'] ?? null,
            ]);

            return $user;
        });

        // Sends the "verify your email" message (Laravel's listener for Registered).
        event(new Registered($user));

        return $user;
    }
}
