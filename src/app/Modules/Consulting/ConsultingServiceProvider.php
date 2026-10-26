<?php

namespace App\Modules\Consulting;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Policies\ConsultantProfilePolicy;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;

/**
 * FR6 Consulting Management — consultant profiles and availability (roadmap
 * 3.11); conflict-free booking, client management and proposals follow in
 * 3.12 – 3.14.
 */
class ConsultingServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Consulting';

    protected function policies(): array
    {
        return [ConsultantProfile::class => ConsultantProfilePolicy::class];
    }

    protected function morphMap(): array
    {
        return ['consultant_profile' => ConsultantProfile::class];
    }

    protected function relations(): array
    {
        return [
            User::class => [
                'consultantProfile' => fn (User $user) => $user->hasOne(ConsultantProfile::class, 'user_id'),
            ],
        ];
    }

    /** Your consultant profile, weekly hours and days off (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'consultant_profile' => fn (User $user) => ($p = ConsultantProfile::with('rules', 'daysOff')->where('user_id', $user->id)->first()) ? [
                'headline' => $p->headline, 'bio' => $p->bio, 'expertise' => $p->expertise, 'session_minutes' => $p->session_minutes,
                'price_pkr' => $p->price_pkr, 'timezone' => $p->timezone,
                'weekly_hours' => $p->rules->map->label()->all(),
                'days_off' => $p->daysOff->map(fn ($d) => ['date' => $d->date->toDateString(), 'reason' => $d->reason])->all(),
            ] : null,
        ];
    }

    /** A consultant who leaves takes their public profile and hours with them. */
    protected function personalDataErasers(): array
    {
        return [fn (User $user) => ConsultantProfile::where('user_id', $user->id)->delete()];
    }
}
