<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectFile;
use App\Modules\Projects\Models\ProjectInvitation;
use App\Modules\Projects\Notifications\InvitationAnswered;
use App\Modules\Projects\Notifications\ProjectInvitationSent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Suite Q — project invitations (3.2) and shared files with versions (3.3).
 * Cases Q1–Q11.
 */
class ProjectCollaborationTest extends AqDeskTestCase
{
    private function project($owner = null): Project
    {
        $owner ??= $this->member();
        $this->actingAs($owner)->post(route('projects.store'), ['name' => 'Thesis team', 'visibility' => 'private']);

        return Project::where('user_id', $owner->id)->latest('id')->firstOrFail();
    }

    /** Invite and return the secret token from the notification the invitee received. */
    private function invite(Project $project, $user, string $role = 'collaborator'): string
    {
        $this->actingAs($project->owner)->post(route('projects.invitations.store', $project), ['email' => $user->email, 'role' => $role])
            ->assertSessionHasNoErrors();
        $url = $user->notifications()->where('type', ProjectInvitationSent::class)->latest()->firstOrFail()->data['url'];

        return basename($url);
    }

    /** Q1 — a member is invited in-app and by email; only a hash of the token is stored. */
    public function test_q1_invite_a_member(): void
    {
        $project = $this->project();
        $token = $this->invite($project, $this->otherMember());

        $invitation = ProjectInvitation::firstOrFail();
        $this->assertSame('pending', $invitation->status);
        $this->assertNotSame($token, $invitation->token_hash);
        $this->assertSame(ProjectInvitation::hashToken($token), $invitation->token_hash);
        $this->assertTrue($invitation->expires_at->between(now()->addDays(6), now()->addDays(8)));
    }

    /** Q2 — someone without an account is emailed the link. */
    public function test_q2_invite_by_email_only(): void
    {
        Notification::fake();
        $project = $this->project();

        $this->actingAs($project->owner)->post(route('projects.invitations.store', $project), ['email' => 'New.Person@Example.org', 'role' => 'viewer'])
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(ProjectInvitationSent::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'new.person@example.org' && $channels === ['mail']);
    }

    /** Q3 — accepting with the matching account makes you a member in the invited role and tells the owner. */
    public function test_q3_accept(): void
    {
        $project = $this->project();
        $sara = $this->otherMember();
        $token = $this->invite($project, $sara, 'viewer');

        $this->signOut();
        $this->get(route('invitations.show', $token))->assertOk()->assertSee('Thesis team')->assertSee('Sign in to accept');

        $this->actingAs($sara)->post(route('invitations.accept', $token))->assertRedirect(route('projects.show', $project));
        $this->assertSame('viewer', $project->roleOf($sara));
        $this->assertSame('accepted', ProjectInvitation::first()->status);
        $this->assertSame(1, $project->owner->notifications()->where('type', InvitationAnswered::class)->count());

        $this->actingAs($sara)->post(route('invitations.accept', $token))->assertSessionHasErrors('invitation');
    }

    /** Q4 — someone else's account cannot use the link; unknown tokens are 404. */
    public function test_q4_wrong_account_and_bad_token(): void
    {
        $project = $this->project();
        $token = $this->invite($project, $this->otherMember());

        $this->actingAs($this->expert())->post(route('invitations.accept', $token))->assertSessionHasErrors('invitation');
        $this->assertNull($project->roleOf($this->expert()));
        $this->get(route('invitations.show', 'not-a-real-token'))->assertNotFound();
    }

    /** Q5 — declining needs no account; expired and cancelled links stop working; resend issues a new link. */
    public function test_q5_decline_expire_cancel_resend(): void
    {
        $project = $this->project();
        $sara = $this->otherMember();
        $token = $this->invite($project, $sara);

        $this->signOut();
        $this->post(route('invitations.decline', $token))->assertRedirect(route('invitations.show', $token));
        $this->assertSame('declined', ProjectInvitation::first()->status);

        $invitation = ProjectInvitation::first();
        $this->travel(1)->minutes();      // so the resent notification is the latest one
        $this->actingAs($project->owner)->put(route('projects.invitations.resend', [$project, $invitation]));
        $fresh = basename($sara->notifications()->where('type', ProjectInvitationSent::class)->latest()->firstOrFail()->data['url']);
        $this->assertNotSame($token, $fresh);
        $this->get(route('invitations.show', $token))->assertNotFound();      // old link dead

        $invitation->refresh()->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($sara)->post(route('invitations.accept', $fresh))->assertSessionHasErrors('invitation');
        $this->get(route('invitations.show', $fresh))->assertSee('This invitation has expired');

        $this->actingAs($project->owner)->delete(route('projects.invitations.cancel', [$project, $invitation]));
        $this->assertSame('cancelled', $invitation->fresh()->status);
    }

    /** Q6 — no inviting existing members or double-inviting; only the owner invites. */
    public function test_q6_invitation_rules(): void
    {
        $project = $this->project();
        $sara = $this->otherMember();
        $this->invite($project, $sara);

        $this->actingAs($project->owner)->post(route('projects.invitations.store', $project), ['email' => $sara->email, 'role' => 'viewer'])
            ->assertSessionHasErrors('email');
        $this->actingAs($project->owner)->post(route('projects.invitations.store', $project), ['email' => $project->owner->email, 'role' => 'viewer'])
            ->assertSessionHasErrors('email');
        $this->actingAs($sara)->post(route('projects.invitations.store', $project), ['email' => 'x@example.org', 'role' => 'viewer'])
            ->assertForbidden();
        $this->actingAs($project->owner)->get(route('projects.show', $project))->assertSee('Invitations waiting')->assertSee($sara->email);
    }

    /** Q7 — collaborators upload; the file is private and starts at version 1. */
    public function test_q7_upload(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $sara = $this->otherMember();
        $token = $this->invite($project, $sara);
        $this->actingAs($sara)->post(route('invitations.accept', $token));

        $this->actingAs($sara)->post(route('projects.files.store', $project), [
            'file' => UploadedFile::fake()->create('proposal.pdf', 300, 'application/pdf'), 'name' => 'Project proposal',
        ])->assertSessionHasNoErrors();

        $file = ProjectFile::firstOrFail();
        $version = $file->versions()->firstOrFail();
        $this->assertSame(['Project proposal', 1, 1], [$file->name, $file->versions_count, $version->number]);
        Storage::disk('local')->assertExists($version->path);
        $this->assertStringStartsWith('projects/'.$project->id.'/', $version->path);
    }

    /** Q8 — new versions are numbered and every version stays downloadable. */
    public function test_q8_versions(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $owner = $project->owner;
        $this->actingAs($owner)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('report.docx', 100)]);
        $file = ProjectFile::firstOrFail();

        $this->actingAs($owner)->post(route('projects.files.versions.store', [$project, $file]), [
            'file' => UploadedFile::fake()->create('report-final.docx', 120), 'note' => 'Added conclusions',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $file->fresh()->versions_count);
        $this->actingAs($owner)->get(route('projects.files.show', [$project, $file]))
            ->assertOk()->assertSee('v2 · latest')->assertSee('Added conclusions')->assertSee('report-final.docx');

        $first = $file->versions()->where('number', 1)->firstOrFail();
        $download = $this->actingAs($owner)->get(route('projects.files.download', [$project, $file, $first]))->assertOk();
        $this->assertStringContainsString('v1 - report.docx', $download->headers->get('Content-Disposition'));
    }

    /** Q9 — viewers download but cannot upload; outsiders get 404; archived projects take no uploads. */
    public function test_q9_file_permissions(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $owner = $project->owner;
        $this->actingAs($owner)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('notes.txt', 5)]);
        $file = ProjectFile::firstOrFail();
        $version = $file->versions()->firstOrFail();
        $viewer = $this->otherMember();
        $token = $this->invite($project, $viewer, 'viewer');
        $this->actingAs($viewer)->post(route('invitations.accept', $token));

        $this->actingAs($viewer)->get(route('projects.files.download', [$project, $file, $version]))->assertOk();
        $this->actingAs($viewer)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('x.txt', 1)])->assertForbidden();
        $this->actingAs($this->expert())->get(route('projects.files.download', [$project, $file, $version]))->assertNotFound();

        $this->actingAs($owner)->put(route('projects.archive', $project), ['archived' => 1]);
        $this->actingAs($owner)->post(route('projects.files.versions.store', [$project, $file]), ['file' => UploadedFile::fake()->create('y.txt', 1)])->assertForbidden();
    }

    /** Q10 — only allowed kinds and sizes; deleting a file removes every stored version. */
    public function test_q10_validation_and_delete(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $owner = $project->owner;

        $this->actingAs($owner)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('file');
        $this->actingAs($owner)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf')])
            ->assertSessionHasErrors('file');

        $this->actingAs($owner)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]);
        $file = ProjectFile::firstOrFail();
        $this->actingAs($owner)->post(route('projects.files.versions.store', [$project, $file]), ['file' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')]);
        $paths = $file->versions()->pluck('path');

        $this->actingAs($owner)->delete(route('projects.files.destroy', [$project, $file]))->assertRedirect();
        $this->assertDatabaseMissing('project_files', ['id' => $file->id]);
        $paths->each(fn ($p) => Storage::disk('local')->assertMissing($p));
    }

    /** Q11 — deleting a project removes its stored files. */
    public function test_q11_project_deletion_removes_files(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $this->actingAs($project->owner)->post(route('projects.files.store', $project), ['file' => UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf')]);
        $path = ProjectFile::firstOrFail()->versions()->value('path');

        $this->actingAs($project->owner)->delete(route('projects.destroy', $project));
        Storage::disk('local')->assertMissing($path);
    }
}
