<?php

namespace App\Modules\Consulting\Services;

use App\Modules\Consulting\Models\AvailabilityException;
use App\Modules\Consulting\Models\AvailabilityRule;
use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/** A consultant's profile, weekly hours and days off (FR6 "Consultant setup", roadmap 3.11). */
class ConsultantService
{
    public const MAX_BLOCKS_PER_DAY = 6;

    /** Create or update your own profile. @param  array<string, mixed>  $data  validated ConsultantProfileRequest data */
    public function save(User $user, array $data): ConsultantProfile
    {
        $profile = ConsultantProfile::firstOrNew(['user_id' => $user->id]);
        $profile->fill($data);
        $profile->slug ??= ConsultantProfile::makeSlug($user->name);
        $profile->save();
        $user->setRelation('consultantProfile', $profile);   // an earlier "no profile yet" lookup is cached on the user

        return $profile;
    }

    /** @throws ValidationException when it overlaps another block that day or the day is full */
    public function addHours(ConsultantProfile $profile, int $weekday, string $starts, string $ends): AvailabilityRule
    {
        $sameDay = $profile->rules()->where('weekday', $weekday)->get();
        if ($sameDay->count() >= self::MAX_BLOCKS_PER_DAY) {
            throw ValidationException::withMessages(['starts' => 'Up to '.self::MAX_BLOCKS_PER_DAY.' blocks per day.']);
        }

        [$from, $to] = [AvailabilityRule::minutes($starts), AvailabilityRule::minutes($ends)];
        $clash = $sameDay->first(fn (AvailabilityRule $r) => $from < AvailabilityRule::minutes($r->ends) && AvailabilityRule::minutes($r->starts) < $to);
        if ($clash) {
            throw ValidationException::withMessages(['starts' => 'This overlaps your hours '.$clash->label().'. Change that block or pick another time.']);
        }
        if ($to - $from < $profile->session_minutes) {
            throw ValidationException::withMessages(['ends' => 'The block must fit at least one '.$profile->session_minutes.'-minute session.']);
        }

        return $profile->rules()->create(['weekday' => $weekday, 'starts' => $starts, 'ends' => $ends]);
    }

    public function removeHours(AvailabilityRule $rule): void
    {
        $rule->delete();
    }

    /** @throws ValidationException when that day is already off */
    public function addDayOff(ConsultantProfile $profile, string $date, ?string $reason): AvailabilityException
    {
        if ($profile->daysOff()->whereDate('date', $date)->exists()) {
            throw ValidationException::withMessages(['date' => 'That day is already marked as a day off.']);
        }

        return $profile->daysOff()->create(['date' => $date, 'reason' => $reason]);
    }

    public function removeDayOff(AvailabilityException $day): void
    {
        $day->delete();
    }
}
