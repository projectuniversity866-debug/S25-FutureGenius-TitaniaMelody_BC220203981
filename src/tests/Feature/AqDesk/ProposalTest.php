<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\ConsultingClient;
use App\Modules\Consulting\Models\Proposal;
use App\Modules\Consulting\Notifications\ProposalUpdate;
use Illuminate\Support\Facades\Notification;

/**
 * Suite PR — proposals Draft → Sent → Accepted / Rejected → Completed
 * (3.14). Cases PR1–PR5.
 */
class ProposalTest extends AqDeskTestCase
{
    private function consultant($user = null): ConsultantProfile
    {
        $user ??= $this->otherExpert();
        $this->actingAs($user)->put(route('consulting.profile.update'), [
            'headline' => 'Code reviews for final-year projects', 'session_minutes' => 60, 'timezone' => 'Asia/Karachi',
            'notice_hours' => 0, 'window_days' => 14, 'is_accepting' => 1,
        ])->assertSessionHasNoErrors();

        return ConsultantProfile::where('user_id', $user->id)->firstOrFail();
    }

    private function client(ConsultantProfile $profile, array $data = []): ConsultingClient
    {
        $this->actingAs($profile->user)->post(route('consulting.clients.store'), $data + ['name' => 'Hina Qureshi', 'status' => 'lead'])->assertSessionHasNoErrors();

        return $profile->clients()->latest('id')->firstOrFail();
    }

    private function draft(ConsultingClient $client, array $data = []): Proposal
    {
        $this->actingAs($client->profile->user)->post(route('consulting.proposals.store', $client), $data + [
            'title' => 'Four review sessions for your project', 'body' => 'Weekly reviews of the **database design** and tests.', 'amount_pkr' => 12000,
        ])->assertSessionHasNoErrors();

        return Proposal::latest('id')->firstOrFail();
    }

    /** PR1 — a draft is private and editable; sending tells the member client and locks it. */
    public function test_pr1_draft_and_send(): void
    {
        Notification::fake();
        $profile = $this->consultant();
        $member = $this->member();
        $client = $this->client($profile, ['email' => $member->email]);
        $proposal = $this->draft($client);

        $this->assertSame('draft', $proposal->status);
        $this->actingAs($profile->user)->get(route('consulting.proposals.show', $proposal))->assertOk()->assertSee('<strong>database design</strong>', false)->assertSee('PKR 12,000');
        $this->actingAs($member)->get(route('consulting.proposals.show', $proposal))->assertNotFound();
        $this->actingAs($profile->user)->put(route('consulting.proposals.update', $proposal), ['title' => 'Five review sessions for your project', 'body' => 'Weekly reviews of the design and tests.'])->assertSessionHasNoErrors();

        $this->actingAs($profile->user)->put(route('consulting.proposals.send', $proposal))->assertSessionHasNoErrors();
        $proposal->refresh();
        $this->assertSame(['sent', 'Five review sessions for your project'], [$proposal->status, $proposal->title]);
        $this->assertNotNull($proposal->sent_at);
        Notification::assertSentTo($member, ProposalUpdate::class);

        $this->actingAs($member)->get(route('consulting.proposals.show', $proposal))->assertOk()->assertSee('Accept');
        $this->actingAs($profile->user)->put(route('consulting.proposals.update', $proposal), ['title' => 'Changed after sending', 'body' => 'Weekly reviews of the design and tests.'])->assertForbidden();
        $this->actingAs($profile->user)->put(route('consulting.proposals.send', $proposal))->assertForbidden();
        $this->actingAs($profile->user)->delete(route('consulting.proposals.destroy', $proposal))->assertForbidden();
    }

    /** PR2 — the client accepts with a note (lead becomes active, consultant told); the consultant completes it. */
    public function test_pr2_accept_and_complete(): void
    {
        $profile = $this->consultant();
        $member = $this->member();
        $client = $this->client($profile, ['email' => $member->email]);
        $proposal = $this->draft($client);
        $this->actingAs($profile->user)->put(route('consulting.proposals.complete', $proposal))->assertForbidden();   // not accepted yet
        $this->actingAs($profile->user)->put(route('consulting.proposals.send', $proposal));

        $this->actingAs($profile->user)->put(route('consulting.proposals.decide', $proposal), ['decision' => 'accepted'])->assertForbidden();   // a member decides for themselves
        $this->actingAs($member)->put(route('consulting.proposals.decide', $proposal), ['decision' => 'accepted', 'note' => 'Can we start next week?'])->assertSessionHasNoErrors();

        $this->assertSame('accepted', $proposal->fresh()->status);
        $this->assertSame('active', $client->fresh()->status);
        $this->assertStringContainsString('Can we start next week?', $profile->user->notifications()->where('data->title', 'like', '%accepted your proposal')->firstOrFail()->data['body']);
        $this->actingAs($member)->put(route('consulting.proposals.decide', $proposal), ['decision' => 'rejected'])->assertForbidden();   // decided once

        $this->actingAs($member)->put(route('consulting.proposals.complete', $proposal))->assertForbidden();
        $this->actingAs($profile->user)->put(route('consulting.proposals.complete', $proposal))->assertSessionHasNoErrors();
        $this->assertSame('completed', $proposal->fresh()->status);
        $this->assertSame(1, $member->notifications()->where('data->title', 'like', 'Proposal completed%')->count());
    }

    /** PR3 — declining, and an expired proposal can no longer be answered. */
    public function test_pr3_reject_and_expiry(): void
    {
        $profile = $this->consultant();
        $member = $this->member();
        $client = $this->client($profile, ['email' => $member->email]);

        $declined = $this->draft($client);
        $this->actingAs($profile->user)->put(route('consulting.proposals.send', $declined));
        $this->actingAs($member)->put(route('consulting.proposals.decide', $declined), ['decision' => 'rejected', 'note' => 'Too expensive for me.']);
        $this->assertSame(['rejected', 'Too expensive for me.'], [$declined->fresh()->status, $declined->fresh()->response_note]);
        $this->actingAs($profile->user)->put(route('consulting.proposals.complete', $declined))->assertForbidden();

        $expiring = $this->draft($client, ['title' => 'Offer valid for one week', 'valid_until' => now()->addWeek()->toDateString()]);
        $this->actingAs($profile->user)->put(route('consulting.proposals.send', $expiring));
        $this->travel(8)->days();
        $this->assertTrue($expiring->fresh()->isExpired());
        $this->actingAs($member)->get(route('consulting.proposals.show', $expiring))->assertSee('Expired')->assertDontSee('Your answer');
        $this->actingAs($member)->put(route('consulting.proposals.decide', $expiring), ['decision' => 'accepted'])->assertForbidden();
    }

    /** PR4 — for a client outside AQDesk the consultant records the answer; outsiders see nothing. */
    public function test_pr4_offline_client_and_access(): void
    {
        $profile = $this->consultant();
        $client = $this->client($profile);   // no account
        $proposal = $this->draft($client);
        $this->actingAs($profile->user)->put(route('consulting.proposals.send', $proposal))->assertSessionHasNoErrors();

        $this->actingAs($this->member())->get(route('consulting.proposals.show', $proposal))->assertNotFound();
        $this->actingAs($this->admin())->get(route('consulting.proposals.show', $proposal))->assertNotFound();
        $other = $this->consultant($this->expert());
        $this->actingAs($other->user)->get(route('consulting.proposals.create', $client))->assertNotFound();
        $this->actingAs($other->user)->post(route('consulting.proposals.store', $client), ['title' => 'Stealing a client', 'body' => 'This should never be saved anywhere.'])->assertForbidden();

        $this->actingAs($profile->user)->put(route('consulting.proposals.decide', $proposal), ['decision' => 'accepted', 'note' => 'Agreed by phone.'])->assertSessionHasNoErrors();
        $this->assertSame('accepted', $proposal->fresh()->status);
        $this->assertSame(1, Proposal::count());
    }

    /** PR5 — validation, the client page list, and both sides' Proposals pages (drafts never reach the client). */
    public function test_pr5_validation_and_lists(): void
    {
        $profile = $this->consultant();
        $member = $this->member();
        $client = $this->client($profile, ['email' => $member->email]);

        $this->actingAs($profile->user)->post(route('consulting.proposals.store', $client), ['title' => 'Hi', 'body' => 'short', 'amount_pkr' => -5, 'valid_until' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors(['title', 'body', 'amount_pkr', 'valid_until']);

        $draft = $this->draft($client, ['title' => 'Still a draft proposal']);
        $sent = $this->draft($client, ['title' => 'Already sent proposal', 'amount_pkr' => null]);
        $this->actingAs($profile->user)->put(route('consulting.proposals.send', $sent));

        $this->actingAs($profile->user)->get(route('consulting.clients.show', $client))->assertSee('Still a draft proposal')->assertSee('Already sent proposal')->assertSee('New proposal');
        $this->actingAs($profile->user)->get(route('consulting.proposals.index', ['status' => 'draft']))->assertSee('Still a draft proposal')->assertDontSee('Already sent proposal');
        $this->actingAs($member)->get(route('consulting.proposals.index'))->assertOk()->assertSee('Already sent proposal')->assertSee('Free')->assertDontSee('Still a draft proposal');
        $this->assertSame('draft', $draft->fresh()->status);
    }
}
