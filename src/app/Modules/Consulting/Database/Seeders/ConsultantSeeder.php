<?php

namespace App\Modules\Consulting\Database\Seeders;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/** A demo consultant (roadmap 3.11): the demo expert, with weekday hours and a lunch break. Adds only what is missing. */
class ConsultantSeeder extends Seeder
{
    public function run(): void
    {
        $expert = User::where('email', 'expert@aqdesk.test')->first();
        if (! $expert || ConsultantProfile::where('user_id', $expert->id)->exists()) {
            return;
        }

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
    }
}
