<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\Consultation;
use App\Modules\Consulting\Notifications\ConsultationUpdate;
use App\Modules\Consulting\Services\AvailabilityService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

/**
 * Suite BK — conflict-free booking (3.12) and suite CR — client management
 * (3.13). Cases BK1–BK8, CR1–CR4.
 */
class ConsultingBookingTest extends AqDeskTestCase
{
    /** Monday 2 Nov 2026, 07:00 in Pakistan; the consultant works Mondays 09:00–12:00 in 60-minute sessions. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-02 07:00', 'Asia/Karachi'));
        Consultation::query()->delete();   // each case starts from an empty calendar (the seeder books a demo session)
    }

    private function consultant($user = null, array $data = []): ConsultantProfile
    {
        $user ??= $this->otherExpert();
        $this->actingAs($user)->put(route('consulting.profile.update'), $data + [
            'headline' => 'Code reviews for final-year projects', 'session_minutes' => 60, 'timezone' => 'Asia/Karachi',
            'notice_hours' => 0, 'window_days' => 14, 'is_accepting' => 1,
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('consulting.hours.store'), ['weekday' => 1, 'starts' => '09:00', 'ends' => '12:00']);

        return ConsultantProfile::where('user_id', $user->id)->firstOrFail();
    }

    /** "2026-11-02 09:00" Pakistan time as the UTC instant the slot links carry. */
    private function at(string $local): string
    {
        return Carbon::parse($local, 'Asia/Karachi')->utc()->toIso8601ZuluString();
    }

    private function book(ConsultantProfile $profile, $client, string $local = '2026-11-02 09:00', string $topic = 'Review of my project database')
    {
        return $this->actingAs($client)->post(route('consultants.book.store', $profile), ['start' => $this->at($local), 'topic' => $topic, 'message' => 'Repository link inside.']);
    }

    private function freeTimes(ConsultantProfile $profile): array
    {
        return app(AvailabilityService::class)->slots($profile->fresh(), 0)->get('2026-11-02', collect())->map->format('H:i')->all();
    }

    /** BK1 — booking takes the slot, makes a Jitsi room, tells both sides and files the client. */
    public function test_bk1_book(): void
    {
        $profile = $this->consultant();
        $client = $this->member();

        $this->actingAs($client)->get(route('consultants.show', $profile))->assertSee(route('consultants.book', [$profile, 'start' => $this->at('2026-11-02 09:00')]), false);
        $this->actingAs($client)->get(route('consultants.book', [$profile, 'start' => $this->at('2026-11-02 09:00')]))->assertOk()->assertSee('Monday 2 November 2026, 09:00 – 10:00 PKT');
        $this->book($profile, $client)->assertSessionHasNoErrors();

        $c = Consultation::firstOrFail();
        $this->assertSame(['confirmed', '04:00', '05:00'], [$c->status, $c->starts_at->format('H:i'), $c->ends_at->format('H:i')]);
        $this->assertStringStartsWith('https://meet.jit.si/AQDesk-Session-', $c->meeting_url);
        $this->assertSame(['10:00', '11:00'], $this->freeTimes($profile));
        $this->assertSame(1, $client->notifications()->where('type', ConsultationUpdate::class)->count());
        $this->assertSame(1, $profile->user->notifications()->where('type', ConsultationUpdate::class)->count());
        $this->assertSame(['booking', 'active', $client->id], [$c->clientRecord->source, $c->clientRecord->status, $c->clientRecord->user_id]);
        $this->actingAs($client)->get(route('consulting.sessions.show', $c))->assertOk()->assertSee('Review of my project database')->assertSee($c->meeting_url, false);
    }

    /** BK2 — a taken slot cannot be booked again; the database refuses a duplicate even past the checks. */
    public function test_bk2_no_double_booking(): void
    {
        $profile = $this->consultant();
        $this->book($profile, $this->member());

        $this->book($profile, $this->otherMember())->assertSessionHasErrors(['start' => 'That time is no longer free — someone may have just booked it. Please pick another.']);
        $this->assertSame(1, Consultation::count());

        $this->expectException(UniqueConstraintViolationException::class);
        $profile->consultations()->create([
            'client_id' => $this->otherMember()->id, 'starts_at' => now(), 'ends_at' => now()->addHour(), 'topic' => 'Sneaky duplicate',
            'slot_key' => Consultation::firstOrFail()->slot_key,
        ]);
    }

    /** BK3 — a client cannot be in two sessions at once, even with different consultants. */
    public function test_bk3_client_overlap(): void
    {
        $a = $this->consultant();
        $b = $this->consultant($this->userWithRole('expert'));
        $client = $this->member();

        $this->book($a, $client)->assertSessionHasNoErrors();
        $this->book($b, $client)->assertSessionHasErrors(['start' => 'You already have a session at that time.']);
        $this->book($b, $client, '2026-11-02 10:00')->assertSessionHasNoErrors();
    }

    /** BK4 — only real free slots: on the grid, inside the hours, not past, not on a day off. */
    public function test_bk4_only_free_slots(): void
    {
        $profile = $this->consultant();
        $client = $this->member();

        foreach (['2026-11-02 09:30', '2026-11-02 12:00', '2026-11-02 06:00', '2026-11-03 09:00'] as $bad) {
            $this->book($profile, $client, $bad)->assertSessionHasErrors('start');
        }
        $this->actingAs($profile->user)->post(route('consulting.days-off.store'), ['date' => '2026-11-09']);
        $this->book($profile, $client, '2026-11-09 09:00')->assertSessionHasErrors('start');
        $this->assertSame(0, Consultation::count());

        $this->actingAs($client)->get(route('consultants.book', [$profile, 'start' => 'not-a-date']))->assertRedirect()->assertSessionHasErrors('start');
        $this->actingAs($client)->post(route('consultants.book.store', $profile), ['start' => $this->at('2026-11-02 10:00'), 'topic' => 'x'])->assertSessionHasErrors('topic');
    }

    /** BK5 — no booking yourself, as a guest, or with a paused consultant. */
    public function test_bk5_who_can_book(): void
    {
        $profile = $this->consultant();

        $this->book($profile, $profile->user)->assertSessionHasErrors(['start' => 'You cannot book a session with yourself.']);
        $this->signOut();
        $this->post(route('consultants.book.store', $profile), ['start' => $this->at('2026-11-02 09:00'), 'topic' => 'Guest booking try'])->assertRedirect(route('login'));
        $this->get(route('consultants.show', $profile))->assertSee('Sign in</a> to book', false);

        $profile->update(['is_accepting' => false]);
        $this->book($profile, $this->member())->assertNotFound();
    }

    /** BK6 — at most five upcoming sessions per client. */
    public function test_bk6_upcoming_limit(): void
    {
        $profile = $this->consultant(null, ['session_minutes' => 30, 'headline' => 'Code reviews for final-year projects', 'timezone' => 'Asia/Karachi', 'notice_hours' => 0, 'window_days' => 14, 'is_accepting' => 1]);
        $client = $this->member();
        foreach (['09:00', '09:30', '10:00', '10:30', '11:00'] as $t) {
            $this->book($profile, $client, '2026-11-02 '.$t)->assertSessionHasNoErrors();
        }
        $this->book($profile, $client, '2026-11-02 11:30')->assertSessionHasErrors('start');
    }

    /** BK7 — either side cancels upcoming sessions; the slot frees up; the other side is told; outsiders get 404. */
    public function test_bk7_cancel(): void
    {
        $profile = $this->consultant();
        $client = $this->member();
        $this->book($profile, $client);
        $c = Consultation::firstOrFail();

        $this->actingAs($this->otherMember())->get(route('consulting.sessions.show', $c))->assertNotFound();
        $this->actingAs($this->otherMember())->put(route('consulting.sessions.cancel', $c))->assertForbidden();

        $this->actingAs($client)->put(route('consulting.sessions.cancel', $c), ['reason' => 'Exam moved to Monday'])->assertSessionHasNoErrors();
        $this->assertSame(['cancelled', null], [$c->fresh()->status, $c->fresh()->slot_key]);
        $this->assertContains('09:00', $this->freeTimes($profile));
        $last = $profile->user->notifications()->where('data->title', 'like', 'Session cancelled%')->firstOrFail()->data;
        $this->assertStringContainsString('Exam moved to Monday', $last['body']);

        $this->book($profile, $this->otherMember())->assertSessionHasNoErrors();     // the same slot again
        $again = Consultation::where('status', 'confirmed')->firstOrFail();
        $this->actingAs($profile->user)->put(route('consulting.sessions.cancel', $again));
        $this->assertSame(1, $this->otherMember()->notifications()->where('data->title', 'Session cancelled by '.$profile->user->name)->count());

        $this->book($profile, $client, '2026-11-02 10:00');
        $this->travelTo(Carbon::parse('2026-11-02 10:30', 'Asia/Karachi'));
        $this->actingAs($client)->put(route('consulting.sessions.cancel', Consultation::where('status', 'confirmed')->firstOrFail()))->assertForbidden();
    }

    /** BK8 — session lists for both sides; the consultant marks a started session done; the join button lights up. */
    public function test_bk8_lists_and_completion(): void
    {
        $profile = $this->consultant();
        $client = $this->member();
        $this->book($profile, $client);
        $c = Consultation::firstOrFail();

        $this->actingAs($client)->get(route('consulting.sessions.index'))->assertSee('Review of my project database')->assertDontSee('Booked with me');
        $this->actingAs($profile->user)->get(route('consulting.sessions.index', ['side' => 'clients']))->assertSee('Review of my project database')->assertSee('Booked with me');
        $this->actingAs($profile->user)->put(route('consulting.sessions.complete', $c))->assertForbidden();   // not started

        $this->travelTo(Carbon::parse('2026-11-02 08:55', 'Asia/Karachi'));
        $this->actingAs($client)->get(route('consulting.sessions.show', $c))->assertSee('Join now');
        $this->travelTo(Carbon::parse('2026-11-02 09:40', 'Asia/Karachi'));
        $this->actingAs($client)->put(route('consulting.sessions.complete', $c))->assertForbidden();
        $this->actingAs($profile->user)->put(route('consulting.sessions.complete', $c))->assertSessionHasNoErrors();
        $this->assertSame('completed', $c->fresh()->status);
        $this->travelTo(Carbon::parse('2026-11-02 11:00', 'Asia/Karachi'));
        $this->actingAs($client)->get(route('consulting.sessions.index', ['tab' => 'past']))->assertSee('Review of my project database');
    }

    /** CR1 — bookers appear automatically; people are added by hand, linked when they are members; no duplicates or self. */
    public function test_cr1_client_list(): void
    {
        $profile = $this->consultant();
        $consultant = $profile->user;
        $this->book($profile, $this->member());

        $this->actingAs($consultant)->get(route('consulting.clients.index'))->assertOk()->assertSee($this->member()->name)->assertSee('AQDesk member');
        $this->actingAs($consultant)->post(route('consulting.clients.store'), ['name' => 'Hina Qureshi', 'email' => 'hina@example.org', 'phone' => '+92 300 1234567', 'organization' => 'Lahore Startups', 'status' => 'lead'])
            ->assertSessionHasNoErrors();
        $this->assertNull($profile->clients()->where('name', 'Hina Qureshi')->value('user_id'));

        $this->actingAs($consultant)->post(route('consulting.clients.store'), ['name' => 'Sara by email', 'email' => strtoupper($this->otherMember()->email), 'status' => 'lead']);
        $sara = $profile->clients()->where('user_id', $this->otherMember()->id)->firstOrFail();
        $this->assertSame('lead', $sara->status);
        $this->book($profile, $this->otherMember(), '2026-11-02 10:00');
        $this->assertSame('active', $sara->fresh()->status);            // a lead who books becomes active
        $this->assertSame(1, Consultation::where('consulting_client_id', $sara->id)->count());

        $this->actingAs($consultant)->post(route('consulting.clients.store'), ['name' => 'Again', 'email' => $this->otherMember()->email, 'status' => 'lead'])->assertSessionHasErrors('email');
        $this->actingAs($consultant)->post(route('consulting.clients.store'), ['name' => 'Me', 'email' => $consultant->email, 'status' => 'lead'])->assertSessionHasErrors('email');
        $this->actingAs($consultant)->post(route('consulting.clients.store'), ['name' => '', 'phone' => 'call me', 'status' => 'vip'])->assertSessionHasErrors(['name', 'phone', 'status']);
        $this->actingAs($this->member())->get(route('consulting.clients.index'))->assertForbidden();
    }

    /** CR2 — notes are the consultant's alone: not the client, not other consultants, not moderators. */
    public function test_cr2_private_notes(): void
    {
        $profile = $this->consultant();
        $this->book($profile, $this->member());
        $client = $profile->clients()->firstOrFail();

        $this->actingAs($profile->user)->post(route('consulting.clients.notes.store', $client), ['body' => 'Needs help with indexes; follow up next week.'])->assertSessionHasNoErrors();
        $this->actingAs($profile->user)->get(route('consulting.clients.show', $client))->assertSee('Needs help with indexes');
        foreach ([$this->member(), $this->expert(), $this->admin()] as $other) {
            $this->actingAs($other)->get(route('consulting.clients.show', $client))->assertNotFound();
        }
        $this->actingAs($this->member())->get(route('consulting.sessions.show', Consultation::firstOrFail()))->assertDontSee('Needs help with indexes');

        $note = $client->notes()->firstOrFail();
        $this->actingAs($this->expert())->delete(route('consulting.clients.notes.destroy', [$client, $note]))->assertNotFound();
        $this->actingAs($profile->user)->delete(route('consulting.clients.notes.destroy', [$client, $note]))->assertRedirect();
        $this->assertSame(0, $client->notes()->count());
    }

    /** CR3 — status tabs, search, editing (a member's name comes from their account), removing keeps sessions. */
    public function test_cr3_manage(): void
    {
        $profile = $this->consultant();
        $consultant = $profile->user;
        $this->book($profile, $this->member());
        $this->actingAs($consultant)->post(route('consulting.clients.store'), ['name' => 'Hina Qureshi', 'organization' => 'Lahore Startups', 'status' => 'lead']);

        $this->actingAs($consultant)->get(route('consulting.clients.index', ['status' => 'lead']))->assertSee('Hina Qureshi')->assertDontSee($this->member()->email);
        $this->actingAs($consultant)->get(route('consulting.clients.index', ['q' => 'startups']))->assertSee('Hina Qureshi');

        $member = $profile->clients()->whereNotNull('user_id')->firstOrFail();
        $this->actingAs($consultant)->put(route('consulting.clients.update', $member), ['name' => 'Renamed', 'email' => 'x@y.org', 'status' => 'past', 'organization' => 'VU']);
        $this->assertSame([$this->member()->name, $this->member()->email, 'past', 'VU'], [$member->fresh()->name, $member->fresh()->email, $member->fresh()->status, $member->fresh()->organization]);

        $this->actingAs($consultant)->delete(route('consulting.clients.destroy', $member))->assertRedirect(route('consulting.clients.index'));
        $this->assertSame(1, Consultation::count());
        $this->assertNull(Consultation::first()->consulting_client_id);
    }

    /** CR4 — exports, and account deletion on either side cancels upcoming sessions and tells the other side. */
    public function test_cr4_privacy(): void
    {
        $profile = $this->consultant();
        $client = $this->userWithRole('member');
        $this->book($profile, $client);
        $this->actingAs($profile->user)->post(route('consulting.clients.notes.store', $profile->clients()->first()), ['body' => 'Private note about the client']);

        $mine = $this->actingAs($client)->get(route('privacy.export'))->json('data');
        $this->assertSame('Review of my project database', $mine['consulting_sessions_booked'][0]['topic']);
        $theirs = $this->actingAs($profile->user)->get(route('privacy.export'))->json('data');
        $this->assertSame(['Private note about the client'], $theirs['consulting_clients'][0]['notes']);

        $this->actingAs($client)->delete(route('privacy.destroy'), ['password' => 'password', 'confirm' => '1']);
        $this->assertSame('cancelled', Consultation::first()->status);
        $this->assertSame('Deleted user', $profile->clients()->first()->name);
        $this->assertStringContainsString('The account was deleted', $profile->user->notifications()->where('data->title', 'like', 'Session cancelled%')->firstOrFail()->data['body']);

        // A consultant who leaves: their clients' upcoming sessions are cancelled and the clients told.
        $other = $this->consultant($this->userWithRole('expert'));
        $this->book($other, $this->member(), '2026-11-02 11:00');
        $name = $other->user->name;
        $this->actingAs($other->user)->delete(route('privacy.destroy'), ['password' => 'password', 'confirm' => '1']);
        $this->assertDatabaseMissing('consultant_profiles', ['id' => $other->id]);
        $this->assertSame(1, $this->member()->notifications()->where('data->title', 'Session cancelled by '.$name)->count());
    }
}
