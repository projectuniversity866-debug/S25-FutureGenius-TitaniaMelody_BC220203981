<?php

namespace App\Modules\Governance\Database\Seeders;

use App\Modules\Community\Models\Answer;
use App\Modules\Governance\Models\Report;
use App\Modules\Governance\Services\PrivacyService;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * Governance demo state (roadmap 2.5 – 2.6): the demo accounts have accepted
 * the current terms, and one report waits in the moderation queue.
 */
class GovernanceSeeder extends Seeder
{
    public function run(): void
    {
        $privacy = app(PrivacyService::class);
        User::query()->each(fn (User $user) => $privacy->accept($user));

        $sara = User::where('email', 'sara@aqdesk.test')->first();
        $answer = Answer::whereHas('user', fn ($q) => $q->where('email', 'faisal@aqdesk.test'))->first();

        if ($sara && $answer && Report::where('reportable_type', 'answer')->where('reportable_id', $answer->id)->doesntExist()) {
            $report = new Report(['reason' => 'off_topic', 'details' => 'This is about Blade helpers, not the access-control question that was asked.']);
            $report->reporter()->associate($sara);
            $report->reportable()->associate($answer);
            $report->save();
        }
    }
}
