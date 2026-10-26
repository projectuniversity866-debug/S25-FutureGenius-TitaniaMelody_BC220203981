<?php

namespace Tests\Feature\AqDesk;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Notifications\EventChanged;
use App\Modules\EventsMedia\Notifications\EventReminder;
use App\Modules\EventsMedia\Notifications\OrganizerReminder;
use App\Modules\EventsMedia\Notifications\RegistrationUpdate;
use Illuminate\Support\Facades\Notification;

/**
 * Suite ZW — webinar links and calendar files (3.8), scheduled reminders
 * (3.9). Cases ZW1–ZW10.
 */
class EventWebinarAndRemindersTest extends AqDeskTestCase
{
    private const ZOOM = 'https://us02web.zoom.us/j/81234567890?pwd=abc';

    private function input(array $data = [], int $hoursAhead = 168): array
    {
        $start = now('Asia/Karachi')->addHours($hoursAhead)->startOfMinute();

        return $data + [
            'title' => 'Machine learning webinar', 'summary' => 'A live walk-through, from data to a model.',
            'format' => 'online', 'timezone' => 'Asia/Karachi', 'meeting_url' => self::ZOOM, 'meeting_notes' => 'Passcode 4821',
            'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
        ];
    }

    private function published(array $data = [], int $hoursAhead = 168): Event
    {
        $this->actingAs($this->expert())->post(route('events.store'), $this->input($data, $hoursAhead))->assertSessionHasNoErrors();
        $event = Event::latest('id')->firstOrFail();
        $this->actingAs($this->expert())->put(route('events.publish', $event));

        return $event->fresh();
    }

    private function seat(Event $event, $user = null)
    {
        $user ??= $this->userWithRole('member');
        $this->actingAs($user)->post(route('events.register.store', $event))->assertSessionHasNoErrors();

        return $user;
    }

    /** ZW1 — the link is checked (https only); its provider is recognised; in-person events drop it. */
    public function test_zw1_meeting_link(): void
    {
        $event = $this->published();
        $this->assertSame('Zoom', $event->meetingProvider());
        $this->assertSame('Google Meet', (new Event(['meeting_url' => 'https://meet.google.com/abc-defg-hij']))->meetingProvider());
        $this->assertSame('Online meeting', (new Event(['meeting_url' => 'https://example.org/room']))->meetingProvider());
        $this->assertSame('Online meeting', (new Event(['meeting_url' => 'https://zoom.us.evil.example/j/1']))->meetingProvider());

        $expert = $this->expert();
        $this->actingAs($expert)->post(route('events.store'), $this->input(['meeting_url' => 'http://zoom.us/j/1']))->assertSessionHasErrors('meeting_url');
        $this->actingAs($expert)->post(route('events.store'), $this->input(['meeting_url' => 'javascript:alert(1)']))->assertSessionHasErrors('meeting_url');
        $this->actingAs($expert)->post(route('events.store'), $this->input(['title' => 'Lab session in person', 'format' => 'in_person', 'venue' => 'Lab 1']));
        $this->assertNull(Event::where('title', 'Lab session in person')->value('meeting_url'));
    }

    /** ZW2 — "create a Jitsi room" makes an unguessable meet.jit.si link, only when none was given. */
    public function test_zw2_jitsi_room(): void
    {
        $event = $this->published(['meeting_url' => '', 'create_jitsi' => '1']);
        $this->assertMatchesRegularExpression('#^https://meet\.jit\.si/AQDesk-MachineLearningWebinar-[A-Za-z0-9]{10}$#', $event->meeting_url);
        $this->assertSame('Jitsi Meet', $event->meetingProvider());

        $kept = $this->published(['title' => 'Webinar with own link', 'create_jitsi' => '1']);
        $this->assertSame(self::ZOOM, $kept->meeting_url);
    }

    /** ZW3 — only people with a seat and the organizer see the link; the waitlist and others do not. */
    public function test_zw3_who_sees_the_link(): void
    {
        $event = $this->published(['capacity' => 1]);
        $seated = $this->seat($event);
        $waiting = $this->seat($event);

        $this->actingAs($seated)->get(route('events.show', $event))->assertSee('Join online')->assertSee(self::ZOOM, false)->assertSee('Passcode 4821');
        $this->actingAs($this->expert())->get(route('events.show', $event))->assertSee(self::ZOOM, false);
        foreach ([$waiting, $this->userWithRole('member')] as $u) {
            $this->actingAs($u)->get(route('events.show', $event))->assertDontSee(self::ZOOM, false)->assertSee('shared with people who have a seat');
        }
        $this->signOut();
        $this->get(route('events.show', $event))->assertDontSee(self::ZOOM, false);
    }

    /** ZW4 — "Join now" lights up shortly before the start, and the link disappears once it has ended. */
    public function test_zw4_join_window(): void
    {
        $event = $this->published([], 2);
        $seated = $this->seat($event);

        $this->actingAs($seated)->get(route('events.show', $event))->assertSee('Open the joining link')->assertDontSee('Join now');
        $this->travelTo($event->starts_at->copy()->subMinutes(10));
        $this->actingAs($seated)->get(route('events.show', $event))->assertSee('Join now')->assertSee('happening now');
        $this->travelTo($event->ends_at->copy()->addMinute());
        $this->actingAs($seated)->get(route('events.show', $event))->assertDontSee(self::ZOOM, false);
    }

    /** ZW5 — the .ics file is valid iCalendar in UTC; the joining link is only in a seat-holder's copy. */
    public function test_zw5_calendar_file(): void
    {
        $event = $this->published(['title' => 'Webinar; with, special characters']);
        $seated = $this->seat($event);

        $this->signOut();
        $public = $this->get(route('events.calendar', $event))->assertOk();
        $this->assertStringStartsWith('text/calendar', $public->headers->get('Content-Type'));
        $ics = $public->getContent();
        foreach (['BEGIN:VCALENDAR', 'BEGIN:VEVENT', 'DTSTART:'.$event->starts_at->format('Ymd\THis\Z'), 'SUMMARY:Webinar\; with\, special characters', 'STATUS:CONFIRMED', 'END:VCALENDAR'] as $line) {
            $this->assertStringContainsString($line, $ics);
        }
        $this->assertStringNotContainsString('zoom.us', $ics);
        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }

        $this->assertStringContainsString('zoom.us', str_replace("\r\n ", '', $this->actingAs($seated)->get(route('events.calendar', $event))->getContent()));

        $this->actingAs($this->expert())->put(route('events.cancel', $event));
        $this->signOut();
        $this->assertStringContainsString('STATUS:CANCELLED', $this->get(route('events.calendar', $event))->getContent());
    }

    /** ZW6 — drafts have no calendar file; the page offers .ics and Google Calendar links. */
    public function test_zw6_calendar_links(): void
    {
        $this->actingAs($this->expert())->post(route('events.store'), $this->input());
        $draft = Event::latest('id')->firstOrFail();
        $this->actingAs($this->expert())->get(route('events.calendar', $draft))->assertNotFound();

        $event = $this->published(['title' => 'Published webinar']);
        $this->signOut();
        $this->get(route('events.show', $event))->assertSee('Add to calendar (.ics)')->assertSee('calendar.google.com/calendar/render', false);
    }

    /** ZW7 — a seat comes with an email carrying the calendar file; changing time or link tells everyone. */
    public function test_zw7_confirmation_and_changes(): void
    {
        Notification::fake();
        $event = $this->published(['capacity' => 1]);
        $seated = $this->seat($event);
        $waiting = $this->seat($event);

        Notification::assertSentTo($seated, RegistrationUpdate::class, function ($n) use ($seated) {
            $mail = $n->toMail($seated);

            return count($mail->rawAttachments) === 1 && str_contains($mail->rawAttachments[0]['data'], 'zoom.us');
        });

        $this->actingAs($this->expert())->put(route('events.update', $event), $this->input(['meeting_url' => 'https://meet.google.com/abc-defg-hij', 'capacity' => 1]));
        Notification::assertSentTo($seated, EventChanged::class, fn ($n) => str_contains($n->toArray($seated)['body'], 'joining link'));
        Notification::assertSentTo($waiting, EventChanged::class, function ($n) use ($waiting) {
            return ! str_contains($n->toMail($waiting)->rawAttachments[0]['data'], 'meet.google.com');   // the waitlist's file has no link
        });

        $this->actingAs($this->expert())->put(route('events.update', $event), $this->input(['meeting_url' => 'https://meet.google.com/abc-defg-hij', 'capacity' => 1, 'summary' => 'Only the summary changed.']));
        Notification::assertSentToTimes($seated, EventChanged::class, 1);
    }

    /** ZW8 — reminders go to people with a seat the day before and an hour before, once each; the organizer gets the head count. */
    public function test_zw8_reminders(): void
    {
        $event = $this->published(['capacity' => 1], 48);
        $seated = $this->seat($event);
        $waiting = $this->seat($event);
        $count = fn ($u, $class) => $u->notifications()->where('type', $class)->count();

        $this->artisan('events:send-reminders')->expectsOutputToContain('Sent 0 attendee')->assertSuccessful();

        $this->travelTo($event->starts_at->copy()->subHours(20));
        $this->artisan('events:send-reminders')->expectsOutputToContain('Sent 1 attendee and 1 organizer')->assertSuccessful();
        $this->artisan('events:send-reminders')->expectsOutputToContain('Sent 0 attendee and 0 organizer');
        $this->assertSame(1, $count($seated, EventReminder::class));
        $this->assertSame(0, $count($waiting, EventReminder::class));
        $this->assertStringContainsString('1 person has a seat and 1 is waiting', $this->expert()->notifications()->where('type', OrganizerReminder::class)->first()->data['body']);

        $this->travelTo($event->starts_at->copy()->subMinutes(45));
        $this->artisan('events:send-reminders')->expectsOutputToContain('Sent 1 attendee');
        $this->assertSame('Starting soon: Machine learning webinar', $seated->notifications()->where('type', EventReminder::class)->latest()->first()->data['title']);

        $this->travelTo($event->starts_at->copy()->addMinutes(5));
        $this->artisan('events:send-reminders')->expectsOutputToContain('Sent 0 attendee');
        $this->assertSame(2, $count($seated, EventReminder::class));
    }

    /** ZW9 — late registrations get only the nearest reminder; cancelled events and cancelled seats get none; a new time re-arms them. */
    public function test_zw9_reminder_edge_cases(): void
    {
        $soon = $this->published(['title' => 'Webinar starting soon'], 1);
        $late = $this->seat($soon);
        $this->artisan('events:send-reminders');
        $this->assertSame(1, $late->notifications()->where('type', EventReminder::class)->count());
        $this->travel(5)->minutes();
        $this->artisan('events:send-reminders');
        $this->assertSame(1, $late->notifications()->where('type', EventReminder::class)->count());

        $cancelled = $this->published(['title' => 'Webinar that is cancelled'], 10);
        $gone = $this->seat($cancelled);
        $this->actingAs($this->expert())->put(route('events.cancel', $cancelled));
        $left = $this->published(['title' => 'Webinar someone leaves'], 10);
        $leaver = $this->seat($left);
        $this->actingAs($leaver)->delete(route('events.register.destroy', $left));
        $this->artisan('events:send-reminders');
        $this->assertSame(0, $gone->notifications()->where('type', EventReminder::class)->count());
        $this->assertSame(0, $leaver->notifications()->where('type', EventReminder::class)->count());

        // Moving an event re-arms its reminders for the new time.
        $moved = $this->published(['title' => 'Webinar that moves'], 10);
        $attendee = $this->seat($moved);
        $this->artisan('events:send-reminders');
        $this->actingAs($this->expert())->put(route('events.update', $moved), $this->input(['title' => 'Webinar that moves'], 30));
        $this->assertNull($moved->registrations()->first()->reminded_day_at);
        $this->travel(10)->hours();
        $this->artisan('events:send-reminders');
        $this->assertSame(2, $attendee->notifications()->where('type', EventReminder::class)->count());
    }

    /** ZW10 — the reminder is scheduled, its email has the joining link and calendar file, and email can be switched off. */
    public function test_zw10_schedule_and_email(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('events:send-reminders')->assertSuccessful();

        $event = $this->published([], 10);
        $seated = $this->seat($event);
        $mail = (new EventReminder($event, 'day'))->toMail($seated);
        $this->assertStringContainsString(self::ZOOM, implode(' ', array_merge($mail->introLines, $mail->outroLines)));
        $this->assertCount(1, $mail->rawAttachments);

        $this->assertSame(['database', 'mail'], (new EventReminder($event, 'day'))->via($seated));
        $seated->forceFill(['settings' => ['email_events' => false]])->save();
        $this->assertSame(['database'], (new EventReminder($event, 'day'))->via($seated->fresh()));
    }
}
