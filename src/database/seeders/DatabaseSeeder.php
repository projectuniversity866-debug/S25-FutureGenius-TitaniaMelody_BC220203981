<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Run with:  php artisan db:seed
 * Or reset everything with:  php artisan migrate:fresh --seed
 *
 * Order matters — roles and categories must exist before users and questions.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            UserSeeder::class,
            QuestionSeeder::class,
            JobSeeder::class,
        ]);
    }
}
