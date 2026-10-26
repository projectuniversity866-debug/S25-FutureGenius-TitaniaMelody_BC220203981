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

        $this->seedWork(Project::where('slug', 'aqdesk-accessibility-audit')->firstOrFail(), $users);
    }

    /** Feedback (3.4) and a task board (3.5) for the audit project; skipped when already there. */
    private function seedWork(Project $project, $users): void
    {
        if (! $project->threads()->exists()) {
            $thread = $project->threads()->create([
                'user_id' => $users['expert@aqdesk.test'], 'title' => 'Colour contrast on the job board badges',
                'body'    => "The light badges on the job board look below 4.5:1 to me.\n\n- Check them with the contrast checker\n- Note the ratios in the audit sheet",
                'last_activity_at' => now(),
            ]);
            $thread->replies()->create(['user_id' => $users['sara@aqdesk.test'], 'body' => 'Checked: two badges were 3.9:1. I darkened them; please look again.']);
            $thread->update(['replies_count' => 1]);
        }

        if (! $project->tasks()->exists()) {
            foreach ([
                ['Audit the sign-in and register pages', 'done', 'high', 'member@aqdesk.test', -6],
                ['Test every form with a screen reader', 'doing', 'high', 'sara@aqdesk.test', 3],
                ['Check keyboard focus order on the dashboard', 'todo', 'normal', 'member@aqdesk.test', 7],
                ['Write up findings for the supervisor', 'todo', 'low', null, 14],
            ] as [$title, $status, $priority, $assignee, $days]) {
                $project->tasks()->create([
                    'created_by' => $users['member@aqdesk.test'], 'assignee_id' => $assignee ? $users[$assignee] : null,
                    'title' => $title, 'status' => $status, 'priority' => $priority, 'due_on' => today()->addDays($days),
                    'completed_at' => $status === 'done' ? now()->subDays(7) : null,
                ]);
            }
        }
    }
}
