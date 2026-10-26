<?php

namespace App\Modules\EventsMedia\Database\Seeders;

use App\Modules\EventsMedia\Models\Event;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Models\Form;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo events (roadmap 3.6 – 3.7): a small in-person workshop that is full
 * with a waitlist, an online webinar with registration questions, and a
 * past meet-up. Adds only what is missing.
 */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');
        $expert = $users['expert@aqdesk.test'];
        $at = fn (int $days, string $time) => Carbon::parse(today('Asia/Karachi')->addDays($days)->toDateString().' '.$time, 'Asia/Karachi')->utc();

        $workshop = $this->event('Hands-on accessibility testing workshop', [
            'user_id' => $expert, 'format' => 'in_person', 'venue' => 'Computer Lab 2, Virtual University Lahore campus',
            'summary' => 'Two hours of testing real pages with a screen reader, the keyboard and contrast tools.',
            'description' => "Bring a laptop. We will:\n\n1. Test a sign-up form with NVDA\n2. Find keyboard traps\n3. Fix colour contrast together",
            'starts_at' => $at(12, '10:00'), 'ends_at' => $at(12, '12:00'), 'capacity' => 2,
        ]);
        $this->registrations($workshop, $users, ['member@aqdesk.test' => 'confirmed', 'sara@aqdesk.test' => 'confirmed', 'faisal@aqdesk.test' => 'waitlisted']);

        $questions = Form::firstOrCreate(['title' => 'Webinar registration questions'], [
            'user_id' => $expert, 'slug' => Form::makeSlug('Webinar registration questions'), 'status' => 'draft',
            'description' => 'Used as the registration form of the machine learning webinar.',
        ]);
        if (! $questions->fields()->exists()) {
            $questions->fields()->create(['type' => 'single_choice', 'label' => 'Your experience with Python', 'options' => ['None yet', 'Beginner', 'Comfortable'], 'is_required' => true, 'position' => 1]);
            $questions->fields()->create(['type' => 'short_text', 'label' => 'One thing you want to learn', 'is_required' => false, 'position' => 2]);
        }
        $webinar = $this->event('Your first machine learning model — live webinar', [
            'user_id' => $expert, 'format' => 'online', 'form_id' => $questions->id,
            'summary' => 'A one-hour live walk-through: from a CSV file to a trained, evaluated model.',
            'description' => 'Follows the Learning Hub path “Your first machine learning project”. Questions welcome throughout.',
            'starts_at' => $at(20, '18:00'), 'ends_at' => $at(20, '19:00'), 'capacity' => 100,
        ]);
        if (! $webinar->meeting_url) {   // roadmap 3.8: a joining link for the demo webinar
            $webinar->update(['meeting_url' => Event::jitsiRoom($webinar->title), 'meeting_notes' => 'Cameras optional. Questions in the chat.']);
        }

        $past = $this->event('AQDesk community meet-up', [
            'user_id' => $users['admin@aqdesk.test'], 'format' => 'hybrid', 'venue' => 'Auditorium, Virtual University Lahore campus',
            'summary' => 'Final-year students and mentors met to share projects and plans.',
            'starts_at' => $at(-20, '16:00'), 'ends_at' => $at(-20, '18:00'),
        ]);
        $this->registrations($past, $users, ['member@aqdesk.test' => 'confirmed']);
    }

    private function event(string $title, array $data): Event
    {
        return Event::firstOrCreate(['title' => $title], $data + ['slug' => Event::makeSlug($title), 'status' => 'published', 'timezone' => 'Asia/Karachi']);
    }

    private function registrations(Event $event, $users, array $people): void
    {
        $i = 0;
        foreach ($people as $email => $status) {
            $event->registrations()->firstOrCreate(['user_id' => $users[$email]], ['status' => $status, 'queued_at' => now()->subDays(5)->addMinutes($i++)]);
        }
    }
}
