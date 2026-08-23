<?php

namespace Database\Seeders;

use App\Models\Role;
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
    }
}
