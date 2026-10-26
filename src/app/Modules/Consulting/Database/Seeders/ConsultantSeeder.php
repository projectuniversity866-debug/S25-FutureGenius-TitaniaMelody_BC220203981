<?php

namespace App\Modules\Consulting\Database\Seeders;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\Consultation;
use App\Modules\Consulting\Services\AvailabilityService;
use App\Modules\Consulting\Services\ClientService;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * A demo consultant (roadmap 3.11): the demo expert, with weekday hours and a
 * lunch break; a booking by the demo member and a hand-added lead (3.12 –
 * 3.13). Adds only what is missing.
 */
class ConsultantSeeder extends Seeder
{
    public function run(): void
    {
        $expert = User::where('email', 'expert@aqdesk.test')->first();
        if (! $expert) {
            return;
        }

        $profile = ConsultantProfile::where('user_id', $expert->id)->first() ?? $this->profile($expert);
        $this->bookingsAndClients($profile);
    }

    private function profile(User $expert): ConsultantProfile
    {
        $profile = ConsultantProfile::create([
            'user_id' => $expert->id, 'slug' => ConsultantProfile::makeSlug($expert->name),
            'headline' => 'Final-year project reviews and Laravel help for students',
            'bio' => "I review final-year projects before the viva: architecture, tests and the demo.\n\n**Bring:** your repository link and one question you are stuck on.",
            'expertise' => ['Laravel', 'Testing', 'Project reviews', 'Accessibility'],
            'session_minutes' => 45, 'price_pkr' => null, 'timezone' => 'Asia/Karachi', 'notice_hours' => 24, 'window_days' => 30,
        ]);

        foreach ([1, 3, 5] as $weekday) {      // Monday, Wednesday, Friday
            $profile->rules()->create(['weekday' => $weekday, 'starts' => '10:00', 'ends' => '12:15']);
            $profile->rules()->create(['weekday' => $weekday, 'starts' => '14:00', 'ends' => '16:15']);
        }
        $profile->rules()->create(['weekday' => 6, 'starts' => '11:00', 'ends' => '12:30']);   // Saturday

        return $profile;
    }

    /** Roadmap 3.12 – 3.13: the demo member's booking in the next free slot, and a lead added by hand with a note. */
    private function bookingsAndClients(ConsultantProfile $profile): void
    {
        $member = User::where('email', 'member@aqdesk.test')->first();
        if ($member && ! $profile->consultations()->exists() && ($start = app(AvailabilityService::class)->next($profile))) {
            $profile->consultations()->create([
                'client_id' => $member->id, 'consulting_client_id' => app(ClientService::class)->forMember($profile, $member)->id,
                'starts_at' => $start->utc(), 'ends_at' => $start->addMinutes($profile->session_minutes)->utc(),
                'topic' => 'Review of my final-year project before the viva', 'message' => 'I would like feedback on my test coverage.',
                'meeting_url' => Consultation::jitsiRoom(), 'status' => 'confirmed', 'slot_key' => Consultation::slotKey($profile->id, $start),
            ]);
        }

        if (! $profile->clients()->where('source', 'manual')->exists()) {
            $lead = $profile->clients()->create([
                'name' => 'Hina Qureshi', 'email' => 'hina.qureshi@example.org', 'organization' => 'Lahore Startup Incubator',
                'status' => 'lead', 'source' => 'manual',
            ]);
            $lead->notes()->create(['body' => 'Met at the community meet-up. Interested in mentoring sessions for her team in December.']);
        }
    }
}
