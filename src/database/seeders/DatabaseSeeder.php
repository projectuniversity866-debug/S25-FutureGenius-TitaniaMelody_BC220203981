<?php

namespace Database\Seeders;

use App\Modules\Careers\Database\Seeders\JobSeeder;
use App\Modules\Community\Database\Seeders\CategorySeeder;
use App\Modules\Community\Database\Seeders\QuestionSeeder;
use App\Modules\Identity\Database\Seeders\RoleSeeder;
use App\Modules\Identity\Database\Seeders\UserSeeder;
use Illuminate\Database\Seeder;

/**
 * Run with:  php artisan db:seed
 * Or reset everything with:  php artisan migrate:fresh --seed
 *
 * Each module owns its seeders; this class only fixes the order.
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
