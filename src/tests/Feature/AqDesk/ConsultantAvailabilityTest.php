<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Services\AvailabilityService;
use Illuminate\Support\Carbon;

/**
 * Suite CS — consultant profiles and availability slots (3.11). Cases CS1–CS8.
 */
class ConsultantAvailabilityTest extends AqDeskTestCase
{
    private function profile($user = null, array $data = []): ConsultantProfile
    {
        $user ??= $this->otherExpert();
        $this->actingAs($user)->put(route('consulting.profile.update'), $data + [
            'headline' => 'Code reviews for final-year projects', 'expertise' => 'Laravel, Testing , ,Vue',
            'session_minutes' => 60, 'timezone' => 'Asia/Karachi', 'notice_hours' => 0, 'window_days' => 14, 'is_accepting' => 1,
        ])->assertSessionHasNoErrors();

        return ConsultantProfile::where('user_id', $user->id)->firstOrFail();
    }

    private function hours($user, int $weekday, string $from, string $to)
    {
        return $this->actingAs($user)->post(route('consulting.hours.store'), ['weekday' => $weekday, 'starts' => $from, 'ends' => $to]);
    }

    /** CS1 — an expert sets up a profile (topics from a comma list); members cannot. */
    public function test_cs1_profile_setup(): void
    {
        $expert = $this->otherExpert();
        $this->actingAs($expert)->get(route('consulting.availability'))->assertRedirect(route('consulting.profile'));
        $profile = $this->profile($expert);

        $this->assertSame(['Laravel', 'Testing', 'Vue'], $profile->expertise);
        $this->assertSame('Free', $profile->priceLabel());
        $this->actingAs($expert)->get(route('consulting.availability'))->assertOk()->assertSee('Weekly hours');

        $this->actingAs($this->member())->get(route('consulting.profile'))->assertForbidden();
        $this->actingAs($this->member())->put(route('consulting.profile.update'), ['headline' => 'I want to consult too please'])->assertForbidden();
        $this->actingAs($expert)->put(route('consulting.profile.update'), ['headline' => 'x', 'session_minutes' => 50, 'timezone' => 'Mars/Base', 'notice_hours' => 0, 'window_days' => 0])
            ->assertSessionHasErrors(['headline', 'session_minutes', 'timezone', 'window_days']);
    }

    /** CS2 — the directory lists consultants taking bookings; a paused profile is visible only to its owner. */
    public function test_cs2_visibility(): void
    {
        $expert = $this->otherExpert();
        $profile = $this->profile($expert, ['price_pkr' => 2500]);

        $this->signOut();
        $this->get(route('consultants.index'))->assertOk()->assertSee($expert->name)->assertSee('PKR 2,500 per session');
        $this->get(route('consultants.show', $profile))->assertOk()->assertSee('Code reviews for final-year projects');

        $this->profile($expert, ['is_accepting' => 0, 'headline' => 'Code reviews for final-year projects']);
        $this->signOut();
        $this->get(route('consultants.index'))->assertDontSee('Code reviews for final-year projects');
        $this->get(route('consultants.show', $profile))->assertNotFound();
        $this->actingAs($expert)->get(route('consultants.show', $profile))->assertOk()->assertSee('Not taking bookings right now');
    }

    /** CS3 — weekly hours: no overlaps, end after start, room for a session; only your own can be removed. */
    public function test_cs3_weekly_hours(): void
    {
        $expert = $this->otherExpert();
        $profile = $this->profile($expert);

        $this->hours($expert, 2, '09:00', '12:00')->assertSessionHasNoErrors();
        $this->hours($expert, 2, '11:00', '13:00')->assertSessionHasErrors('starts');
        $this->hours($expert, 2, '12:00', '13:00')->assertSessionHasNoErrors();      // touching is fine
        $this->hours($expert, 3, '15:00', '14:00')->assertSessionHasErrors('ends');
        $this->hours($expert, 3, '15:00', '15:30')->assertSessionHasErrors('ends');   // shorter than a 60-minute session
        $this->hours($expert, 8, '09:00', '10:00')->assertSessionHasErrors('weekday');
        $this->assertSame(2, $profile->rules()->count());

        $rule = $profile->rules()->first();
        $this->actingAs($this->expert())->delete(route('consulting.hours.destroy', $rule))->assertNotFound();
        $this->actingAs($expert)->delete(route('consulting.hours.destroy', $rule))->assertRedirect();
        $this->assertSame(1, $profile->rules()->count());
    }

    /** CS4 — slots: blocks cut into sessions in the consultant's zone, stored as real instants. */
    public function test_cs4_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 07:00', 'Asia/Karachi'));   // a Monday morning
        $expert = $this->otherExpert();
        $profile = $this->profile($expert);
        $this->hours($expert, 1, '09:00', '11:30');
        $this->hours($expert, 1, '14:00', '15:00');

        $monday = app(AvailabilityService::class)->slots($profile->fresh(), 0)->get('2026-11-02');
        $this->assertSame(['09:00', '10:00', '14:00'], $monday->map->format('H:i')->all());
        $this->assertSame('04:00', $monday->first()->utc()->format('H:i'));     // 09:00 PKT

        $this->signOut();
        $this->get(route('consultants.show', $profile))->assertSee('Monday 2 November')->assertSee('10:00');
        $this->get(route('consultants.index'))->assertSee('Next free: Mon 2 Nov, 09:00 PKT');
    }

    /** CS5 — notice period, booking window and days off remove slots; days off are validated. */
    public function test_cs5_notice_window_days_off(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 08:30', 'Asia/Karachi'));   // Monday
        $expert = $this->otherExpert();
        $profile = $this->profile($expert, ['notice_hours' => 2, 'window_days' => 8, 'headline' => 'Code reviews for final-year projects']);
        $this->hours($expert, 1, '09:00', '13:00');

        $slots = app(AvailabilityService::class)->slots($profile->fresh());
        $this->assertSame(['11:00', '12:00'], $slots->get('2026-11-02')->map->format('H:i')->all());   // 09:00, 10:00 too soon
        $this->assertTrue($slots->has('2026-11-09'));      // next Monday is inside 8 days
        $this->assertFalse($slots->has('2026-11-16'));

        $this->actingAs($expert)->post(route('consulting.days-off.store'), ['date' => '2026-11-09', 'reason' => 'Exams'])->assertSessionHasNoErrors();
        $this->actingAs($expert)->post(route('consulting.days-off.store'), ['date' => '2026-11-09'])->assertSessionHasErrors('date');
        $this->actingAs($expert)->post(route('consulting.days-off.store'), ['date' => '2026-10-01'])->assertSessionHasErrors('date');
        $this->assertFalse(app(AvailabilityService::class)->slots($profile->fresh())->has('2026-11-09'));

        $this->signOut();
        $this->get(route('consultants.show', $profile))->assertDontSee('Exams');   // the note is private
    }

    /** CS6 — daylight saving: 09:00 London stays 09:00 local when the clocks change; the instant moves. */
    public function test_cs6_daylight_saving(): void
    {
        $this->travelTo(Carbon::parse('2026-10-19 06:00', 'Europe/London'));   // Monday before the UK clocks go back (25 Oct)
        $expert = $this->otherExpert();
        $profile = $this->profile($expert, ['timezone' => 'Europe/London', 'headline' => 'Code reviews for final-year projects']);
        $this->hours($expert, 1, '09:00', '10:00');

        $slots = app(AvailabilityService::class)->slots($profile->fresh());
        $before = $slots->get('2026-10-19')->first();
        $after = $slots->get('2026-10-26')->first();
        $this->assertSame(['09:00', '09:00'], [$before->format('H:i'), $after->format('H:i')]);
        $this->assertSame(['08:00', '09:00'], [$before->utc()->format('H:i'), $after->utc()->format('H:i')]);
    }

    /** CS7 — search by topic or name; the seeded consultant is listed with hours. */
    public function test_cs7_directory(): void
    {
        $this->profile($this->otherExpert(), ['expertise' => 'Kubernetes', 'headline' => 'Cloud deployment advice for student projects']);

        $this->get(route('consultants.index', ['q' => 'kubernetes']))->assertSee('Cloud deployment advice');
        $this->get(route('consultants.index', ['q' => 'Kubernetes']))->assertDontSee('Final-year project reviews and Laravel help');
        $this->get(route('consultants.index', ['q' => ['x']]))->assertOk();

        $seeded = ConsultantProfile::where('user_id', $this->expert()->id)->firstOrFail();
        $this->assertSame(7, $seeded->rules()->count());
        $this->get(route('consultants.index'))->assertSee('Final-year project reviews and Laravel help')->assertSee('45-minute sessions');
    }

    /** CS8 — the export holds your profile and hours; deleting your account removes the profile. */
    public function test_cs8_privacy(): void
    {
        $expert = $this->userWithRole('expert');
        $profile = $this->profile($expert);
        $this->hours($expert, 4, '10:00', '11:00');

        $data = $this->actingAs($expert)->get(route('privacy.export'))->json('data');
        $this->assertSame(['Thursday 10:00–11:00'], $data['consultant_profile']['weekly_hours']);

        $this->actingAs($expert)->delete(route('privacy.destroy'), ['password' => 'password', 'confirm' => '1']);
        $this->assertDatabaseMissing('consultant_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('availability_rules', ['consultant_profile_id' => $profile->id]);
    }
}
