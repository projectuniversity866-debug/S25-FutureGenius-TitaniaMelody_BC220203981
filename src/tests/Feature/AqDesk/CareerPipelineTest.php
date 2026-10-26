<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Careers\Models\JobAlert;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Notifications\ApplicationStatusUpdated;
use App\Modules\Careers\Notifications\MatchingOpportunity;
use App\Modules\Careers\Notifications\OpportunityClosed;
use Illuminate\Support\Facades\Notification;

/**
 * Suite W — application pipeline (1.14) and career tools: saved
 * opportunities, deadline closing, job alerts (1.15). Cases W1–W9.
 */
class CareerPipelineTest extends AqDeskTestCase
{
    private function application(): JobApplication
    {
        $job = $this->postJobAs($this->expert(), ['title' => 'Pipeline test opportunity']);
        $this->applyAs($this->member(), $job)->assertSessionHasNoErrors();

        return $job->applications()->firstOrFail();
    }

    /** W1 — submitting and every status change are kept with who, when and the message. */
    public function test_w1_status_history(): void
    {
        $application = $this->application();
        $this->assertSame([[null, 'pending']], $application->statusChanges->map(fn ($c) => [$c->from_status, $c->to_status])->all());

        $this->actingAs($this->expert())->put(route('applications.status', $application), [
            'status' => 'shortlisted', 'message' => 'Interview on Monday at 11:00.',
        ])->assertSessionHasNoErrors();

        $last = $application->fresh()->statusChanges->last();
        $this->assertSame(['pending', 'shortlisted', 'Interview on Monday at 11:00.', $this->expert()->id],
            [$last->from_status, $last->to_status, $last->message, $last->user_id]);

        $note = $this->member()->notifications()->where('type', ApplicationStatusUpdated::class)->firstOrFail();
        $this->assertStringContainsString('Interview on Monday at 11:00.', $note->data['body']);
        $this->assertSame(route('applications.show', $application), $note->data['url']);
    }

    /** W2 — the applicant sees progress and messages, never private notes; strangers get 403. */
    public function test_w2_who_sees_what(): void
    {
        $application = $this->application();
        $this->actingAs($this->expert())->put(route('applications.status', $application), ['status' => 'reviewed', 'message' => 'We are reading it.']);
        $this->actingAs($this->expert())->post(route('applications.notes.store', $application), ['body' => 'Secret: strong candidate'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->member())->get(route('applications.show', $application))->assertOk()
            ->assertSee('Progress')->assertSee('We are reading it.')->assertSee('aria-current="step"', false)
            ->assertDontSee('Secret: strong candidate')->assertDontSee('Private notes');

        $this->actingAs($this->expert())->get(route('applications.show', $application))->assertOk()
            ->assertSee('Secret: strong candidate')->assertSee('Update status');

        $this->actingAs($this->otherMember())->get(route('applications.show', $application))->assertForbidden();
        $this->actingAs($this->admin())->get(route('applications.show', $application))->assertOk()->assertSee('Private notes');
    }

    /** W3 — only reviewers add notes; the writer or a moderator deletes them. */
    public function test_w3_notes(): void
    {
        $application = $this->application();

        $this->actingAs($this->member())->post(route('applications.notes.store', $application), ['body' => 'Let me in'])->assertForbidden();
        $this->actingAs($this->expert())->post(route('applications.notes.store', $application), ['body' => 'Good portfolio']);
        $note = $application->notes()->firstOrFail();

        $this->actingAs($this->member())->delete(route('applications.notes.destroy', $note))->assertForbidden();
        $this->actingAs($this->admin())->delete(route('applications.notes.destroy', $note))->assertRedirect();
        $this->assertSame(0, $application->notes()->count());
    }

    /** W4 — saving opportunities: form and JSON, twice harmless, listed privately under Saved. */
    public function test_w4_saved_opportunities(): void
    {
        $job = $this->postJobAs($this->expert(), ['title' => 'An opportunity worth saving']);
        $member = $this->member();

        $this->actingAs($member)->post(route('jobs.save', $job))->assertSessionHas('status');
        $this->actingAs($member)->postJson(route('jobs.save', $job))->assertOk()->assertJson(['saved' => true, 'count' => 1]);

        $this->actingAs($member)->get(route('saved.index', ['tab' => 'opportunities']))->assertOk()
            ->assertSee('An opportunity worth saving');
        $this->actingAs($member)->get(route('jobs.show', $job))->assertSee('aria-pressed="true"', false);
        $this->actingAs($this->otherMember())->get(route('saved.index', ['tab' => 'opportunities']))
            ->assertDontSee('An opportunity worth saving');

        $this->actingAs($member)->deleteJson(route('jobs.unsave', $job))->assertOk()->assertJson(['saved' => false, 'count' => 0]);
    }

    /** W5 — the scheduler closes past-deadline opportunities, logs it and tells the poster; re-publishing reopens. */
    public function test_w5_deadline_closing(): void
    {
        $expired = $this->postJobAs($this->expert(), ['title' => 'Deadline already passed']);
        $this->applyAs($this->member(), $expired);
        $expired->forceFill(['deadline' => now()->subDay()])->save();
        $open = $this->postJobAs($this->expert(), ['title' => 'Still open for a month']);

        $this->artisan('careers:close-expired')->expectsOutputToContain('Closed')->assertSuccessful();

        $this->assertFalse($expired->fresh()->is_active);
        $this->assertNotNull($expired->fresh()->closed_at);
        $this->assertTrue($open->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'opportunity.closed', 'subject_id' => $expired->id]);
        $closed = $this->expert()->notifications()->where('type', OpportunityClosed::class)->get();
        $this->assertCount(1, $closed);
        $this->assertStringContainsString('1 application to review', $closed->first()->data['body']);

        $this->actingAs($this->expert())->put(route('jobs.update', $expired), $this->jobPayload([
            'title' => 'Deadline already passed', 'deadline' => now()->addWeek()->format('Y-m-d'), 'is_active' => 1,
        ]))->assertSessionHasNoErrors();
        $this->assertNull($expired->fresh()->closed_at);
        $this->assertTrue($expired->fresh()->isOpen());
    }

    /** W6 — alerts: one notification per member however many alerts match; never the poster; non-matches stay quiet. */
    public function test_w6_job_alerts_notify_once(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post(route('alerts.store'), ['keywords' => 'laravel'])->assertSessionHasNoErrors();
        $this->actingAs($member)->post(route('alerts.store'), ['type' => 'job', 'work_mode' => 'remote']);
        $this->actingAs($this->otherMember())->post(route('alerts.store'), ['type' => 'hackathon']);
        $this->actingAs($this->expert())->post(route('alerts.store'), ['keywords' => 'laravel']); // the poster's own

        $this->postJobAs($this->expert(), ['title' => 'Remote Laravel Engineer', 'work_mode' => 'remote']);

        $this->assertSame(1, $member->notifications()->where('type', MatchingOpportunity::class)->count());
        $this->assertSame(0, $this->otherMember()->notifications()->where('type', MatchingOpportunity::class)->count());
        $this->assertSame(0, $this->expert()->notifications()->where('type', MatchingOpportunity::class)->count());
        $this->assertNotNull(JobAlert::where('user_id', $member->id)->whereNotNull('last_sent_at')->first());
    }

    /** W7 — alert rules: no duplicates, at most 10, email follows the setting, only the owner deletes. */
    public function test_w7_alert_rules(): void
    {
        $member = $this->member();
        $this->actingAs($member)->post(route('alerts.store'), ['keywords' => 'design']);
        $this->actingAs($member)->post(route('alerts.store'), ['keywords' => 'design'])
            ->assertSessionHasErrors(['alert' => 'You already have an alert for exactly this search.']);

        foreach (range(2, JobAlert::MAX_PER_USER) as $i) {
            $this->actingAs($member)->post(route('alerts.store'), ['keywords' => "topic {$i}"]);
        }
        $this->actingAs($member)->post(route('alerts.store'), ['keywords' => 'one too many'])->assertSessionHasErrors('alert');
        $this->assertSame(JobAlert::MAX_PER_USER, JobAlert::where('user_id', $member->id)->count());

        $this->actingAs($member)->get(route('alerts.index'))->assertOk()->assertSee('“design”');
        $alert = JobAlert::where('user_id', $member->id)->where('keywords', 'topic 2')->firstOrFail();
        $this->actingAs($this->otherMember())->delete(route('alerts.destroy', $alert))->assertForbidden();
        $this->actingAs($member)->delete(route('alerts.destroy', $alert))->assertRedirect();

        Notification::fake();
        $this->postJobAs($this->expert(), ['title' => 'Senior design lead']);
        Notification::assertSentTo($member, MatchingOpportunity::class, fn ($n, $channels) => $channels === ['database', 'mail']);
    }

    /** W8 — the board offers an alert for the current search; invalid criteria are rejected. */
    public function test_w8_alert_from_the_board(): void
    {
        $this->actingAs($this->member())->get(route('jobs.index', ['type' => 'grant', 'mode' => 'remote', 'q' => 'data']))
            ->assertOk()->assertSee('Create job alert')
            ->assertSee('<input type="hidden" name="type" value="grant">', false)
            ->assertSee('<input type="hidden" name="work_mode" value="remote">', false);

        $this->actingAs($this->member())->post(route('alerts.store'), ['type' => 'party', 'work_mode' => 'space'])
            ->assertSessionHasErrors(['type', 'work_mode']);
    }

    /** W9 — seeded applications already have their history. */
    public function test_w9_seeded_history(): void
    {
        $this->assertSame(0, JobApplication::doesntHave('statusChanges')->count());
        $this->assertSame(JobApplication::count(), JobApplication::whereHas('statusChanges', fn ($q) => $q->whereNull('from_status'))->count());
    }
}
