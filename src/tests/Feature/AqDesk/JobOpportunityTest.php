<?php

namespace Tests\Feature\AqDesk;

use App\Models\JobApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Suite F — Jobs and opportunities (Module 6). Cases F1–F12.
 */
class JobOpportunityTest extends AqDeskTestCase
{
    /** F1 — an expert can publish an internship. */
    public function test_f1_expert_can_publish_an_internship(): void
    {
        $job = $this->postJobAs($this->expert(), [
            'title' => 'Machine Learning Intern',
            'type'  => 'internship',
        ]);

        $this->assertSame('internship', $job->type);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('Machine Learning Intern');
    }

    /** F2 — a maximum salary below the minimum is rejected. */
    public function test_f2_inverted_salary_range_is_rejected(): void
    {
        $this->actingAs($this->expert())
            ->post(route('jobs.store'), $this->jobPayload([
                'salary_min' => 200000,
                'salary_max' => 50000,
            ]))
            ->assertSessionHasErrors('salary_max');
    }

    /** F3 — a deadline in the past is rejected. */
    public function test_f3_past_deadline_is_rejected(): void
    {
        $this->actingAs($this->expert())
            ->post(route('jobs.store'), $this->jobPayload([
                'deadline' => now()->subWeek()->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('deadline');
    }

    /** F4 — a member can apply with a cover letter and a PDF resume. */
    public function test_f4_member_can_apply_with_a_resume(): void
    {
        Storage::fake('public');

        $job    = $this->postJobAs($this->expert());
        $member = $this->member();

        $this->applyAs($member, $job, [
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $job->applications()->where('user_id', $member->id)->count());

        $this->actingAs($member)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee('already applied');
    }

    /** F5 — a cover letter under thirty characters is rejected. */
    public function test_f5_short_cover_letter_is_rejected(): void
    {
        $job = $this->postJobAs($this->expert());

        $this->applyAs($this->member(), $job, ['cover_letter' => 'Too short.'])
            ->assertSessionHasErrors('cover_letter');
    }

    /** F6 — a second application to the same job is prevented. */
    public function test_f6_duplicate_application_is_prevented(): void
    {
        $job    = $this->postJobAs($this->expert());
        $member = $this->member();

        $this->applyAs($member, $job)->assertSessionHasNoErrors();
        $this->applyAs($member, $job);

        $this->assertSame(1, $job->applications()->where('user_id', $member->id)->count());
    }

    /** F7 — the application is listed with a status badge. */
    public function test_f7_application_appears_in_my_applications(): void
    {
        $job    = $this->postJobAs($this->expert(), ['title' => 'A tracked opportunity title']);
        $member = $this->member();

        $this->applyAs($member, $job);

        $this->actingAs($member)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee('A tracked opportunity title');
    }

    /** F8 — the owner sees the applicant and their cover letter. */
    public function test_f8_owner_sees_the_applicant(): void
    {
        $expert = $this->expert();
        $job    = $this->postJobAs($expert);
        $member = $this->member();

        $this->applyAs($member, $job, [
            'cover_letter' => 'A memorable cover letter phrase that is long enough to pass.',
        ]);

        $this->actingAs($expert)
            ->get(route('jobs.applicants', $job))
            ->assertOk()
            ->assertSee($member->name)
            ->assertSee('A memorable cover letter phrase');
    }

    /** F9 — the owner can change the applicant's status. */
    public function test_f9_owner_can_shortlist_an_applicant(): void
    {
        $expert = $this->expert();
        $job    = $this->postJobAs($expert);
        $member = $this->member();

        $this->applyAs($member, $job);

        $application = JobApplication::where('user_id', $member->id)->latest('id')->firstOrFail();

        $this->actingAs($expert)
            ->put(route('applications.status', $application), ['status' => 'shortlisted'])
            ->assertSessionHasNoErrors();

        $this->assertSame('shortlisted', $application->fresh()->status);
    }

    /** F10 — a member can withdraw and then re-apply. */
    public function test_f10_member_can_withdraw_an_application(): void
    {
        $job    = $this->postJobAs($this->expert());
        $member = $this->member();

        $this->applyAs($member, $job);

        $application = JobApplication::where('user_id', $member->id)->latest('id')->firstOrFail();

        $this->actingAs($member)
            ->delete(route('applications.destroy', $application))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('job_applications', ['id' => $application->id]);

        $this->actingAs($member)
            ->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee(route('jobs.apply', $job));
    }

    /** F11 — type and work-mode filters both apply. */
    public function test_f11_type_and_mode_filters_apply(): void
    {
        $expert = $this->expert();

        $this->postJobAs($expert, [
            'title'     => 'Remote internship listing probe',
            'type'      => 'internship',
            'work_mode' => 'remote',
        ]);

        $this->postJobAs($expert, [
            'title'     => 'Onsite job listing probe',
            'type'      => 'job',
            'work_mode' => 'onsite',
        ]);

        $this->get(route('jobs.index', ['type' => 'internship', 'mode' => 'remote']))
            ->assertOk()
            ->assertSee('Remote internship listing probe')
            ->assertDontSee('Onsite job listing probe');
    }

    /** F12 — an unpublished opportunity leaves the public board. */
    public function test_f12_admin_can_unpublish_an_opportunity(): void
    {
        $job = $this->postJobAs($this->expert(), ['title' => 'An opportunity to be unpublished']);

        $this->actingAs($this->admin())
            ->put(route('admin.jobs.toggle', $job))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $job->fresh()->is_active);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertDontSee('An opportunity to be unpublished');
    }
}
