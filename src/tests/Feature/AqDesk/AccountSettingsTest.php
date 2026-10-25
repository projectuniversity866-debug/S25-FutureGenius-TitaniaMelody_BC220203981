<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Community\Notifications\NewAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Suite K — sign-in protection, account settings (1.4) and dashboard
 * to-do / activity (1.5). Cases K1–K8.
 */
class AccountSettingsTest extends AqDeskTestCase
{
    /** K1 — five wrong passwords lock that email for a minute, even with the right one. */
    public function test_k1_login_is_rate_limited(): void
    {
        RateLimiter::clear('member@aqdesk.test|127.0.0.1');
        $email = $this->member()->email;

        foreach (range(1, 5) as $_) {
            $this->post(route('login.store'), ['email' => $email, 'password' => 'wrong-password']);
        }

        $this->post(route('login.store'), ['email' => $email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertMatchesRegularExpression('/^Too many sign-in attempts\. Please try again in \d+ seconds?\.$/',
            session('errors')->first('email'));
        $this->assertGuest();
    }

    /** K2 — turning off answer emails keeps the in-app notification only. */
    public function test_k2_email_preferences_are_respected(): void
    {
        Notification::fake();
        $author = $this->member();

        $this->actingAs($author)->put(route('settings.update'), ['profile_visibility' => 'public', 'email_applications' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertFalse($author->fresh()->wantsEmailFor('answers'));
        $this->assertTrue($author->fresh()->wantsEmailFor('applications'));

        $this->answerQuestionAs($this->otherMember(), $this->askQuestionAs($author->fresh()));

        Notification::assertSentTo($author, NewAnswer::class, fn ($n, $channels) => $channels === ['database']);
    }

    /** K3 — a members-only profile is hidden from guests but visible to members. */
    public function test_k3_members_only_profile(): void
    {
        $member = $this->member();
        $this->actingAs($member)->put(route('settings.update'), ['profile_visibility' => 'members']);
        $this->signOut();

        $this->get(route('profile.show', $member))->assertNotFound();
        $this->actingAs($this->otherMember())->get(route('profile.show', $member))->assertOk();
    }

    /** K4 — the settings page lists signed-in devices; others can be signed out with the password. */
    public function test_k4_sessions_list_and_sign_out_others(): void
    {
        config(['session.driver' => 'database']);
        $member = $this->member();
        DB::table('sessions')->insert([
            ['id' => 'other-device', 'user_id' => $member->id, 'ip_address' => '10.0.0.7',
             'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/124.0 Mobile', 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $this->actingAs($member)->get(route('settings.show'))->assertOk()->assertSee('Chrome on Android')->assertSee('10.0.0.7');

        $this->actingAs($member)->delete(route('settings.sessions.destroy'), ['password' => 'nope'])
            ->assertSessionHasErrors('password');
        $this->assertDatabaseHas('sessions', ['id' => 'other-device']);

        $this->actingAs($member)->delete(route('settings.sessions.destroy'), ['password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
    }

    /** K5 — invalid visibility values are rejected. */
    public function test_k5_invalid_visibility_rejected(): void
    {
        $this->actingAs($this->member())->put(route('settings.update'), ['profile_visibility' => 'everyone-ever'])
            ->assertSessionHasErrors('profile_visibility');
    }

    /** K6 — an expert's to-do points at their pending applicants. */
    public function test_k6_expert_todo_lists_pending_applicants(): void
    {
        $expert = $this->expert();
        $job = $this->postJobAs($expert, ['title' => 'A role with someone waiting']);
        $this->applyAs($this->member(), $job);

        $this->actingAs($expert)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Review 1 pending applicant for “A role with someone waiting”')
            ->assertSee(route('jobs.applicants', [$job, 'status' => 'pending']), false);
    }

    /** K7 — a member is reminded to accept an answer; the feed shows their notifications. */
    public function test_k7_member_todo_and_feed(): void
    {
        $member = $this->userWithRole('member');
        $question = $this->askQuestionAs($member, ['title' => 'Which answer should I accept here today']);
        $this->answerQuestionAs($this->otherMember(), $question);

        $this->actingAs($member)->get(route('dashboard'))
            ->assertSee('Accept the answer that solved “Which answer should I accept here today”')
            ->assertSee('Recent activity')
            ->assertSee('answered “Which answer should I accept here today”');
    }

    /** K8 — admins see the audit trail in their feed. */
    public function test_k8_admin_feed_shows_activity_log(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.users.active', $this->disposableMember()));

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSee('Deactivated the account of');
    }
}
