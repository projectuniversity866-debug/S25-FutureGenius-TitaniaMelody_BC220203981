<?php

namespace Tests\Feature\AqDesk;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Models\EventRegistration;
use App\Modules\EventsMedia\Notifications\EventCancelled;
use App\Modules\EventsMedia\Notifications\RegistrationUpdate;
use App\Modules\Platform\Models\Form;
use Illuminate\Support\Carbon;

/**
 * Suite Z — events with capacity and a waitlist (3.6) and registration
 * through the Form Builder (3.7). Cases Z1–Z12.
 */
class EventsTest extends AqDeskTestCase
{
    /** Form input for an event a week from now, typed in Pakistan time. */
    private function input(array $data = []): array
    {
        $start = now('Asia/Karachi')->addWeek()->setTime(10, 0);

        return $data + [
            'title' => 'Accessibility testing workshop', 'summary' => 'Two hours testing real pages with a screen reader.',
            'format' => 'in_person', 'venue' => 'Lab 2', 'timezone' => 'Asia/Karachi',
            'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_at' => $start->copy()->addHours(2)->format('Y-m-d\TH:i'),
        ];
    }

    private function published(array $data = [], $organizer = null): Event
    {
        $organizer ??= $this->expert();
        $this->actingAs($organizer)->post(route('events.store'), $this->input($data))->assertSessionHasNoErrors();
        $event = Event::latest('id')->firstOrFail();
        $this->actingAs($organizer)->put(route('events.publish', $event));

        return $event->fresh();
    }

    private function register(Event $event, $user, array $answers = [])
    {
        return $this->actingAs($user)->post(route('events.register.store', $event), ['answers' => $answers]);
    }

    private function person(): \App\Modules\Identity\Models\User
    {
        return $this->userWithRole('member');
    }

    /** Z1 — an expert saves a draft (times stored in UTC), only they see it, publishing lists it. */
    public function test_z1_create_and_publish(): void
    {
        $expert = $this->expert();
        $this->actingAs($expert)->post(route('events.store'), $this->input())->assertSessionHasNoErrors();
        $event = Event::latest('id')->firstOrFail();

        $this->assertSame('draft', $event->status);
        $this->assertSame('05:00', $event->starts_at->format('H:i'));          // 10:00 PKT = 05:00 UTC
        $this->assertStringContainsString('10:00 – 12:00 PKT', $event->whenLabel());

        $this->signOut();
        $this->get(route('events.show', $event))->assertNotFound();
        $this->actingAs($this->member())->get(route('events.show', $event))->assertNotFound();
        $this->actingAs($expert)->put(route('events.publish', $event));
        $this->signOut();
        $this->get(route('events.index'))->assertOk()->assertSee('Accessibility testing workshop');
        $this->get(route('events.show', $event))->assertOk()->assertSee('Sign in to register')->assertSee('10:00 – 12:00 PKT');
    }

    /** Z2 — members cannot organize; dates, venue and the registration form are validated. */
    public function test_z2_rules(): void
    {
        $this->actingAs($this->member())->get(route('events.create'))->assertForbidden();
        $this->actingAs($this->member())->post(route('events.store'), $this->input())->assertForbidden();

        $expert = $this->expert();
        $past = now('Asia/Karachi')->subDay()->format('Y-m-d\TH:i');
        $this->actingAs($expert)->post(route('events.store'), $this->input(['starts_at' => $past]))->assertSessionHasErrors('starts_at');
        $this->actingAs($expert)->post(route('events.store'), $this->input(['ends_at' => $past]))->assertSessionHasErrors('ends_at');
        $this->actingAs($expert)->post(route('events.store'), $this->input(['venue' => '']))->assertSessionHasErrors('venue');
        $this->actingAs($expert)->post(route('events.store'), $this->input(['format' => 'online', 'venue' => '']))->assertSessionHasNoErrors();

        $someoneElses = Form::create(['user_id' => $this->admin()->id, 'title' => 'Admin form', 'slug' => 'admin-form-x', 'status' => 'open']);
        $this->actingAs($expert)->post(route('events.store'), $this->input(['form_id' => $someoneElses->id]))->assertSessionHasErrors('form_id');
    }

    /** Z3 — seats go first come, first served; then a waitlist with positions; each person is told. */
    public function test_z3_capacity_and_waitlist(): void
    {
        $event = $this->published(['capacity' => 2]);
        [$a, $b, $c, $d] = [$this->person(), $this->person(), $this->person(), $this->person()];

        $this->register($event, $a)->assertSessionHasNoErrors();
        $this->register($event, $b);
        $this->register($event, $c)->assertSessionHas('status', fn ($s) => str_contains($s, 'number 1 on the waitlist'));
        $this->travel(1)->seconds();
        $this->register($event, $d);

        $status = fn ($u) => $event->registrations()->where('user_id', $u->id)->value('status');
        $this->assertSame(['confirmed', 'confirmed', 'waitlisted', 'waitlisted'], [$status($a), $status($b), $status($c), $status($d)]);
        $this->assertSame(2, $event->registrations()->where('user_id', $d->id)->first()->waitlistPosition());
        $this->assertStringContainsString('waitlist', $c->notifications()->where('type', RegistrationUpdate::class)->first()->data['title']);
        $this->actingAs($d)->get(route('events.show', $event))->assertSee('You are number 2 on the waitlist');
        $this->signOut();
        $this->get(route('events.index'))->assertSee('Full · waitlist open');
    }

    /** Z4 — a cancelled seat goes to the first person waiting, who is told; leaving the waitlist frees no seat. */
    public function test_z4_promotion(): void
    {
        $event = $this->published(['capacity' => 1]);
        [$a, $b, $c] = [$this->person(), $this->person(), $this->person()];
        $this->register($event, $a);
        $this->register($event, $b);
        $this->travel(1)->seconds();
        $this->register($event, $c);

        $this->actingAs($c)->delete(route('events.register.destroy', $event))->assertSessionHas('status', 'You left the waitlist.');
        $this->actingAs($a)->delete(route('events.register.destroy', $event));

        $this->assertSame('confirmed', $event->registrations()->where('user_id', $b->id)->value('status'));
        $this->assertNotNull($event->registrations()->where('user_id', $b->id)->value('promoted_at'));
        $this->assertSame('cancelled', $event->registrations()->where('user_id', $c->id)->value('status'));
        $this->assertSame(1, $b->notifications()->where('data->title', 'A seat opened up — you are in')->count());
    }

    /** Z5 — more seats move the waitlist up; capacity cannot drop below the seats taken. */
    public function test_z5_capacity_changes(): void
    {
        $expert = $this->expert();
        $event = $this->published(['capacity' => 1], $expert);
        [$a, $b, $c] = [$this->person(), $this->person(), $this->person()];
        foreach ([$a, $b, $c] as $u) {
            $this->register($event, $u);
            $this->travel(1)->seconds();
        }

        $edit = fn (array $d) => $this->actingAs($expert)->put(route('events.update', $event), array_merge($this->input(), [
            'starts_at' => $event->local($event->starts_at)->format('Y-m-d\TH:i'), 'ends_at' => $event->local($event->ends_at)->format('Y-m-d\TH:i'),
        ], $d));
        $edit(['capacity' => 2])->assertSessionHasNoErrors();
        $this->assertSame(2, $event->confirmedCount());
        $this->assertSame('waitlisted', $event->registrations()->where('user_id', $c->id)->value('status'));
        $edit(['capacity' => 1])->assertSessionHasErrors('capacity');
        $edit(['capacity' => ''])->assertSessionHasNoErrors();      // no limit: everyone in
        $this->assertSame(3, $event->confirmedCount());
    }

    /** Z6 — no double registration; returning goes to the back of the line; closed and guest cases. */
    public function test_z6_registration_rules(): void
    {
        $event = $this->published(['capacity' => 1]);
        [$a, $b] = [$this->person(), $this->person()];
        $this->register($event, $a);
        $this->register($event, $a)->assertSessionHasErrors('registration');

        $this->actingAs($a)->delete(route('events.register.destroy', $event));
        $this->register($event, $b);
        $this->register($event, $a);
        $this->assertSame('waitlisted', $event->registrations()->where('user_id', $a->id)->value('status'));
        $this->assertSame(1, $event->registrations()->where('user_id', $a->id)->count());   // the same row, reused

        $this->signOut();
        $this->post(route('events.register.store', $event))->assertRedirect(route('login'));

        $event->update(['registration_closes_at' => now()->subMinute()]);
        $this->actingAs($this->person())->post(route('events.register.store', $event))->assertForbidden();
        $this->actingAs($this->person())->get(route('events.show', $event))->assertSee('Registration is closed');
    }

    /** Z7 — cancelling tells everyone (by email whatever their settings); events with people cannot be deleted. */
    public function test_z7_cancel_and_delete(): void
    {
        $expert = $this->expert();
        $event = $this->published(['capacity' => 1], $expert);
        [$a, $b] = [$this->person(), $this->person()];
        $this->register($event, $a);
        $this->register($event, $b);
        $a->forceFill(['settings' => ['email_events' => false]])->save();

        $this->actingAs($expert)->delete(route('events.destroy', $event))->assertForbidden();
        $this->actingAs($expert)->put(route('events.cancel', $event), ['reason' => 'The speaker is ill.'])->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $event->fresh()->status);
        foreach ([$a, $b] as $u) {
            $this->assertSame(1, $u->notifications()->where('type', EventCancelled::class)->count());
        }
        $this->assertSame(['database', 'mail'], (new EventCancelled($event))->via($a->fresh()));
        $this->assertDatabaseHas('activity_logs', ['action' => 'event.cancelled']);
        $this->actingAs($this->person())->get(route('events.show', $event))->assertSee('This event was cancelled')->assertSee('The speaker is ill.');

        $this->actingAs($expert)->post(route('events.store'), $this->input(['title' => 'A draft to delete']));
        $draft = Event::where('title', 'A draft to delete')->firstOrFail();
        $this->actingAs($expert)->delete(route('events.destroy', $draft))->assertRedirect();
        $this->assertDatabaseMissing('events', ['id' => $draft->id]);
    }

    /** Z8 — the attendee list is for the organizer and moderators; removing someone tells them and frees the seat. */
    public function test_z8_attendees(): void
    {
        $expert = $this->expert();
        $event = $this->published(['capacity' => 1], $expert);
        [$a, $b] = [$this->person(), $this->person()];
        $this->register($event, $a);
        $this->register($event, $b);

        $this->actingAs($this->member())->get(route('events.attendees', $event))->assertForbidden();
        $this->actingAs($this->admin())->get(route('events.attendees', $event))->assertOk();
        $this->actingAs($expert)->get(route('events.attendees', $event))->assertOk()->assertSee($a->email)->assertDontSee($b->email);
        $this->actingAs($expert)->get(route('events.attendees', [$event, 'tab' => 'waitlisted']))->assertSee($b->email);

        $registration = $event->registrations()->where('user_id', $a->id)->firstOrFail();
        $this->actingAs($expert)->delete(route('events.attendees.remove', [$event, $registration]));
        $this->assertSame('cancelled', $registration->fresh()->status);
        $this->assertSame('confirmed', $event->registrations()->where('user_id', $b->id)->value('status'));
        $this->assertSame(1, $a->notifications()->where('data->title', 'Your registration was cancelled')->count());
    }

    /** A form with one required choice and one optional text question, owned by the expert. */
    private function questions(): Form
    {
        $form = Form::create(['user_id' => $this->expert()->id, 'title' => 'Workshop questions', 'slug' => 'workshop-questions-x', 'status' => 'draft']);
        $form->fields()->create(['type' => 'single_choice', 'label' => 'Experience level', 'options' => ['Beginner', 'Expert'], 'is_required' => true, 'position' => 1]);
        $form->fields()->create(['type' => 'short_text', 'label' => 'Dietary needs', 'is_required' => false, 'position' => 2]);

        return $form;
    }

    /** Z9 — with a form, registering means answering its questions; answers are validated and stored. */
    public function test_z9_registration_questions(): void
    {
        $form = $this->questions();
        $event = $this->published(['form_id' => $form->id]);
        [$level, $diet] = $form->fields->pluck('id');
        $a = $this->person();

        $this->actingAs($a)->get(route('events.show', $event))->assertSee('The organizer asks 2 questions');
        $this->actingAs($a)->get(route('events.register', $event))->assertOk()->assertSee('Experience level')->assertSee('Dietary needs');
        $this->register($event, $a, [$diet => 'None'])->assertSessionHasErrors("answers.$level");
        $this->register($event, $a, [$level => 'Wizard'])->assertSessionHasErrors("answers.$level");
        $this->assertSame(0, $event->registrations()->count());

        $this->register($event, $a, [$level => 'Beginner', $diet => 'Vegetarian'])->assertSessionHasNoErrors();
        $registration = $event->registrations()->firstOrFail();
        $this->assertSame(['Beginner', 'Vegetarian'], $registration->response->answers()->orderBy('form_field_id')->pluck('value')->all());
        $this->assertSame(1, $form->responses()->count());     // a draft form still collects registration answers
    }

    /** Z10 — answers appear on the attendee list and in the CSV (formula-safe); re-registering replaces them. */
    public function test_z10_answers_for_the_organizer(): void
    {
        $form = $this->questions();
        $event = $this->published(['form_id' => $form->id]);
        [$level, $diet] = $form->fields->pluck('id');
        $a = $this->person();
        $this->register($event, $a, [$level => 'Expert', $diet => '=HYPERLINK("x")']);

        $this->actingAs($this->expert())->get(route('events.attendees', $event))->assertSee('Experience level')->assertSee('Expert');
        $csv = $this->actingAs($this->expert())->get(route('events.attendees.export', $event))->assertOk()->streamedContent();
        $this->assertStringContainsString('Experience level', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);

        $this->actingAs($a)->delete(route('events.register.destroy', $event));
        $this->register($event, $a, [$level => 'Beginner']);
        $this->assertSame(1, $form->responses()->count());
        $this->assertSame('Beginner', $event->registrations()->first()->response->answers()->value('value'));
    }

    /** Z11 — list tabs: upcoming, past, my registrations, organizing; format filter; no form → one-click. */
    public function test_z11_lists(): void
    {
        $expert = $this->expert();
        $webinar = $this->published(['title' => 'Online webinar about testing', 'format' => 'online', 'venue' => ''], $expert);
        $this->published(['title' => 'Workshop in the lab today']);
        $this->actingAs($expert)->post(route('events.store'), $this->input(['title' => 'A secret draft event']));
        $member = $this->member();
        $this->register($webinar, $member);
        $this->actingAs($member)->get(route('events.register', $webinar))->assertRedirect(route('events.show', $webinar));

        $this->actingAs($member)->get(route('events.index', ['format' => 'online']))->assertSee('Online webinar about testing')->assertDontSee('Workshop in the lab today');
        $this->actingAs($member)->get(route('events.index', ['tab' => 'mine']))->assertSee('Online webinar about testing')->assertDontSee('Workshop in the lab today');
        $this->actingAs($member)->get(route('events.index'))->assertDontSee('A secret draft event')->assertDontSee('Organizing');
        $this->actingAs($expert)->get(route('events.index', ['tab' => 'organizing']))->assertSee('A secret draft event');

        $this->travel(9)->days();
        $this->actingAs($member)->get(route('events.index', ['tab' => 'past']))->assertSee('Online webinar about testing');
        $this->actingAs($member)->get(route('events.show', $webinar))->assertSee('This event has ended');
    }

    /** Z12 — deleting your account frees your seat for the waitlist; the export lists your registrations. */
    public function test_z12_privacy(): void
    {
        $event = $this->published(['capacity' => 1]);
        $leaver = $this->userWithRole('member');
        $waiting = $this->person();
        $this->register($event, $leaver);
        $this->register($event, $waiting);

        $data = $this->actingAs($leaver)->get(route('privacy.export'))->json('data');
        $this->assertSame('Accessibility testing workshop', $data['event_registrations'][0]['event']);

        $this->actingAs($leaver)->delete(route('privacy.destroy'), ['password' => 'password', 'confirm' => '1']);
        $this->assertSame(0, EventRegistration::where('user_id', $leaver->id)->count());
        $this->assertSame('confirmed', $event->registrations()->where('user_id', $waiting->id)->value('status'));
    }
}
