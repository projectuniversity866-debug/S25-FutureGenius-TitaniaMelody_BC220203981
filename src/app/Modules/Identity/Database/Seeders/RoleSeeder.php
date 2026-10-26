<?php

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * The three roles the prototype implements. The Main Requirement File asks
 * for "role-based access control" but does not name the roles, so these were
 * derived from the user types listed in its Introduction.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'        => 'Administrator',
                'slug'        => Role::ADMIN,
                'description' => 'Manages users, roles and all community content.',
            ],
            [
                'name'        => 'Expert / Professional',
                'slug'        => Role::EXPERT,
                'description' => 'Answers questions and publishes jobs and internships.',
            ],
            [
                'name'        => 'Member',
                'slug'        => Role::MEMBER,
                'description' => 'Student or community member. Asks, answers and applies.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }

        // Default grants. Only adds — never removes what an admin has changed.
        foreach (PermissionCatalog::all() as $slug => $p) {
            $permission = Permission::updateOrCreate(['slug' => $slug], ['name' => $p['name'], 'group' => $p['group']]);
            $permission->roles()->syncWithoutDetaching(Role::whereIn('slug', $p['roles'])->pluck('id'));
        }
    }
}
