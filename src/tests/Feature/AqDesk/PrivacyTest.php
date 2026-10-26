<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Governance\Models\Consent;
use App\Modules\Governance\Support\LegalDocuments;
use App\Modules\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Suite Y — privacy and data rights (2.6): consent, download my data,
 * delete my account. Cases Y1–Y7.
 */
class PrivacyTest extends AqDeskTestCase
{
    /** Y1 — signing up requires agreeing, and records the version agreed to. */
    public function test_y1_consent_at_registration(): void
    {
        $payload = ['name' => 'New Person', 'email' => 'new.person@example.org', 'password' => 'password123',
            'password_confirmation' => 'password123', 'role_slug' => 'member'];

        $this->post(route('register'), $payload)->assertSessionHasErrors('terms');
        $this->post(route('register'), $payload + ['terms' => '1'])->assertSessionHasNoErrors();

        $user = User::where('email', 'new.person@example.org')->firstOrFail();
        $this->assertDatabaseHas('consents', ['user_id' => $user->id, 'version' => LegalDocuments::VERSION]);
    }

    /** Y2 — members who have not accepted the current version see a banner until they do. */
    public function test_y2_consent_banner(): void
    {
        $user = $this->userWithRole('member');

        $this->actingAs($user)->get(route('dashboard'))->assertSee('Please review our');
        $this->actingAs($user)->post(route('privacy.accept'))->assertRedirect();
        $this->actingAs($user)->get(route('dashboard'))->assertDontSee('Please review our');

        // Seeded demo accounts have already accepted.
        $this->actingAs($this->member())->get(route('dashboard'))->assertDontSee('Please review our');
    }

    /** Y3 — the export contains every module's section and only the member's own data. */
    public function test_y3_download_my_data(): void
    {
        $member = $this->member();
        $this->askQuestionAs($member, ['title' => 'A question that should appear in my export']);
        $this->askQuestionAs($this->otherMember(), ['title' => 'Someone else’s question entirely']);

        $response = $this->actingAs($member)->get(route('privacy.export'))->assertOk();
        $this->assertStringContainsString('attachment; filename="aqdesk-my-data-', $response->headers->get('Content-Disposition'));

        $data = $response->json('data');
        foreach (['account', 'profile', 'questions', 'answers', 'applications', 'saved_opportunities', 'learning_progress',
                  'notifications', 'form_responses', 'consents'] as $section) {
            $this->assertArrayHasKey($section, $data, "Missing section: {$section}");
        }
        $this->assertSame('member@aqdesk.test', $data['account']['email']);
        $titles = array_column($data['questions'], 'title');
        $this->assertContains('A question that should appear in my export', $titles);
        $this->assertNotContains('Someone else’s question entirely', $titles);

        $this->signOut();
        $this->get(route('privacy.export'))->assertRedirect(route('login'));
    }

    /** Y4 — deleting needs the password and the tick. */
    public function test_y4_delete_needs_confirmation(): void
    {
        $user = $this->userWithRole('member');

        $this->actingAs($user)->delete(route('privacy.destroy'), ['password' => 'wrong'])->assertSessionHasErrors(['password', 'confirm']);
        $this->assertTrue($user->fresh()->is_active);
    }

    /** Y5 — deletion erases personal data, keeps public posts as "Deleted user", and blocks sign-in. */
    public function test_y5_delete_account(): void
    {
        Storage::fake('local');
        $user = $this->userWithRole('member');
        $user->profile->update(['bio' => 'My private bio', 'headline' => 'Student at Somewhere']);
        $q = $this->askQuestionAs($user, ['title' => 'A question that outlives its author']);
        $job = $this->postJobAs($this->expert());
        $this->applyAs($user, $job, ['resume' => UploadedFile::fake()->create('cv.pdf', 40, 'application/pdf')]);
        $cv = $job->applications()->where('user_id', $user->id)->value('resume_path');
        $this->actingAs($user)->post(route('bookmarks.store', $q));
        $email = $user->email;

        $this->actingAs($user)->delete(route('privacy.destroy'), ['password' => 'password', 'confirm' => '1'])
            ->assertRedirect(route('home'));
        $this->assertGuest();

        $user->refresh();
        $this->assertSame('Deleted user', $user->name);
        $this->assertFalse($user->is_active);
        $this->assertNotSame($email, $user->email);
        $this->assertNull($user->profile->fresh()->bio);
        $this->assertSame(0, $job->applications()->where('user_id', $user->id)->count());
        Storage::disk('local')->assertMissing($cv);
        $this->assertSame(0, $user->bookmarks()->count());
        $this->assertSame(0, Consent::where('user_id', $user->id)->count());

        $this->get(route('questions.show', $q))->assertOk()->assertSee('Deleted user');
        $this->post(route('login'), ['email' => $email, 'password' => 'password'])->assertSessionHasErrors();
        $this->assertDatabaseHas('activity_logs', ['action' => 'account.deleted']);
    }

    /** Y6 — the last administrator cannot delete their account. */
    public function test_y6_last_admin_is_protected(): void
    {
        User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->where('email', '!=', 'admin@aqdesk.test')->update(['is_active' => false]);

        $this->actingAs($this->admin())->delete(route('privacy.destroy'), ['password' => 'password', 'confirm' => '1'])
            ->assertSessionHasErrors('confirm');
        $this->assertTrue($this->admin()->is_active);
    }

    /** Y7 — the "Privacy & my data" page explains both rights and lists the export's sections. */
    public function test_y7_privacy_page(): void
    {
        $this->actingAs($this->member())->get(route('privacy.data'))->assertOk()
            ->assertSee('Download my data')->assertSee('Delete my account')->assertSee('saved opportunities')
            ->assertSee('You accepted the current version');
    }
}
