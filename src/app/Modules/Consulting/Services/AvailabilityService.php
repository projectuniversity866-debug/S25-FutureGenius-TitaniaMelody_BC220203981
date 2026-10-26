<?php

namespace App\Modules\Consulting\Services;

use App\Modules\Consulting\Models\AvailabilityRule;
use App\Modules\Consulting\Models\ConsultantProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Bookable slots (roadmap 3.11): the weekly hours cut into sessions of the
 * consultant's length, in their own time zone (so daylight-saving changes
 * are handled), without days off, sooner than the notice period or later
 * than the booking window. Roadmap 3.12 also removes slots already booked.
 */
class AvailabilityService
{
    /**
     * @return Collection<string, Collection<int, CarbonImmutable>> local date "Y-m-d" => slot starts (in the consultant's zone), soonest first
     */
    public function slots(ConsultantProfile $profile, ?int $days = null): Collection
    {
        $tz = $profile->timezone;
        $now = CarbonImmutable::now();
        $earliest = $now->addHours($profile->notice_hours);
        $latest = $now->addDays($profile->window_days);
        $length = $profile->session_minutes;

        $rules = $profile->rules->groupBy('weekday');
        $daysOff = $profile->daysOff->map(fn ($d) => $d->date->format('Y-m-d'))->flip();
        $today = CarbonImmutable::now($tz)->startOfDay();

        $out = collect();
        for ($d = 0; $d <= min($days ?? $profile->window_days, $profile->window_days); $d++) {
            $date = $today->addDays($d);
            $key = $date->format('Y-m-d');
            if ($daysOff->has($key)) {
                continue;
            }

            $slots = collect();
            foreach ($rules->get($date->isoWeekday(), []) as $rule) {
                $end = AvailabilityRule::minutes($rule->ends);
                for ($t = AvailabilityRule::minutes($rule->starts); $t + $length <= $end; $t += $length) {
                    $start = CarbonImmutable::parse($key.' '.sprintf('%02d:%02d', intdiv($t, 60), $t % 60), $tz);
                    if ($start->lt($earliest) || $start->gt($latest)) {
                        continue;
                    }
                    $slots->push($start);
                }
            }
            if ($slots->isNotEmpty()) {
                $out->put($key, $slots->sort()->values());
            }
        }

        return $out;
    }

    public function next(ConsultantProfile $profile): ?CarbonImmutable
    {
        return $this->slots($profile)->first()?->first();
    }
}
