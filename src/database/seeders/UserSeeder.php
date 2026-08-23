<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts. Every account uses the password "password".
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::pluck('id', 'slug');

        $users = [
            [
                'name'    => 'Ayesha Khan',
                'email'   => 'admin@aqdesk.test',
                'role'    => Role::ADMIN,
                'profile' => [
                    'headline'     => 'Platform Administrator, AQDesk',
                    'bio'          => 'Maintains the AQDesk community, moderates content and manages member roles.',
                    'skills'       => 'Community Management, Moderation, Digital Governance',
                    'organization' => 'AQDesk',
                    'location'     => 'Islamabad, Pakistan',
                ],
            ],
            [
                'name'    => 'Dr. Bilal Ahmed',
                'email'   => 'expert@aqdesk.test',
                'role'    => Role::EXPERT,
                'profile' => [
                    'headline'     => 'AI Researcher & Lecturer',
                    'bio'          => 'Researches applied machine learning in education. Mentors students and posts internship openings.',
                    'skills'       => 'Machine Learning, Python, Research Methods, EdTech',
                    'organization' => 'National University of Sciences',
                    'location'     => 'Lahore, Pakistan',
                ],
            ],
            [
                'name'    => 'Hina Raza',
                'email'   => 'hr@aqdesk.test',
                'role'    => Role::EXPERT,
                'profile' => [
                    'headline'     => 'Talent Lead at CodeCraft Solutions',
                    'bio'          => 'Hires junior developers and interns for a growing software house.',
                    'skills'       => 'Recruitment, Technical Hiring',
                    'organization' => 'CodeCraft Solutions',
                    'location'     => 'Karachi, Pakistan',
                ],
            ],
            [
                'name'    => 'Usman Tariq',
                'email'   => 'member@aqdesk.test',
                'role'    => Role::MEMBER,
                'profile' => [
                    'headline'     => 'Final year Computer Science student',
                    'bio'          => 'Building web applications with Laravel and learning about accessible design.',
                    'skills'       => 'PHP, Laravel, MySQL, Bootstrap',
                    'organization' => 'Virtual University of Pakistan',
                    'location'     => 'Faisalabad, Pakistan',
                    'github'       => 'https://github.com/',
                ],
            ],
            [
                'name'    => 'Sara Malik',
                'email'   => 'sara@aqdesk.test',
                'role'    => Role::MEMBER,
                'profile' => [
                    'headline'     => 'MS Data Science student',
                    'bio'          => 'Interested in NLP and the policy side of artificial intelligence.',
                    'skills'       => 'Python, NLP, Statistics',
                    'organization' => 'Virtual University of Pakistan',
                    'location'     => 'Multan, Pakistan',
                ],
            ],
            [
                'name'    => 'Faisal Nadeem',
                'email'   => 'faisal@aqdesk.test',
                'role'    => Role::MEMBER,
                'profile' => [
                    'headline'     => 'Frontend developer',
                    'bio'          => 'Cares about accessible interfaces and design systems.',
                    'skills'       => 'JavaScript, Vue.js, Accessibility, CSS',
                    'organization' => 'Freelance',
                    'location'     => 'Peshawar, Pakistan',
                ],
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'password'  => 'password',   // hashed by the model cast
                    'role_id'   => $roles[$data['role']],
                    'is_active' => true,
                ]
            );

            Profile::updateOrCreate(['user_id' => $user->id], $data['profile']);
        }
    }
}
