<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The four categories named explicitly in FR2 of the Main Requirement File.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'EdTech',
                'slug'        => 'edtech',
                'description' => 'Learning technology, online education and digital pedagogy.',
                'icon'        => 'mortarboard',
            ],
            [
                'name'        => 'Artificial Intelligence',
                'slug'        => 'artificial-intelligence',
                'description' => 'Machine learning, LLMs, data science and applied AI.',
                'icon'        => 'cpu',
            ],
            [
                'name'        => 'Web Development',
                'slug'        => 'web-development',
                'description' => 'Frontend, backend, frameworks, databases and deployment.',
                'icon'        => 'code-slash',
            ],
            [
                'name'        => 'Internet Governance',
                'slug'        => 'internet-governance',
                'description' => 'Digital policy, data protection and online rights.',
                'icon'        => 'globe2',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
