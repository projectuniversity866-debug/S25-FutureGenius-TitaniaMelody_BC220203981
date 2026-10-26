<?php

namespace App\Modules\Projects\Database\Seeders;

use App\Modules\Identity\Models\User;
use App\Modules\Projects\Models\Project;
use Illuminate\Database\Seeder;

/** Demo project spaces (roadmap 3.1): a final-year project team and an open study group. */
class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');

        $projects = [
            ['AQDesk accessibility audit', 'member@aqdesk.test', 'private',
                'Final-year project work: auditing every AQDesk screen against WCAG 2.1 AA and fixing what we find.',
                ['sara@aqdesk.test' => 'collaborator', 'expert@aqdesk.test' => 'viewer']],
            ['Machine learning study group', 'expert@aqdesk.test', 'members',
                'Weekly reading group working through the Learning Hub path “Your first machine learning project”. Everyone is welcome.',
                ['faisal@aqdesk.test' => 'collaborator']],
        ];

        foreach ($projects as [$name, $owner, $visibility, $description, $members]) {
            $project = Project::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($name)], [
                'user_id' => $users[$owner], 'name' => $name, 'description' => $description, 'visibility' => $visibility,
            ]);
            $project->members()->syncWithoutDetaching([$users[$owner] => ['role' => 'owner']]);
            foreach ($members as $email => $role) {
                $project->members()->syncWithoutDetaching([$users[$email] => ['role' => $role]]);
            }
        }
    }
}
