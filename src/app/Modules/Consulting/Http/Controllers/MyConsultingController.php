<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Http\Requests\AvailabilityRuleRequest;
use App\Modules\Consulting\Http\Requests\ConsultantProfileRequest;
use App\Modules\Consulting\Http\Requests\DayOffRequest;
use App\Modules\Consulting\Models\AvailabilityException;
use App\Modules\Consulting\Models\AvailabilityRule;
use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Services\AvailabilityService;
use App\Modules\Consulting\Services\ConsultantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A consultant's own profile, weekly hours and days off (FR6 "Consultant setup", roadmap 3.11). */
class MyConsultingController extends Controller
{
    public function __construct(private ConsultantService $consultants, private AvailabilityService $availability) {}

    public function edit(Request $request): View
    {
        $this->authorize('offer', ConsultantProfile::class);

        return view('consulting::mine.profile', ['profile' => $request->user()->consultantProfile]);
    }

    public function update(ConsultantProfileRequest $request): RedirectResponse
    {
        $isNew = ! $request->user()->consultantProfile;
        $this->consultants->save($request->user(), $request->profileData());

        return $isNew
            ? redirect()->route('consulting.availability')->with('status', 'Profile saved. Now add the hours you are available each week.')
            : back()->with('status', 'Profile saved.');
    }

    public function availability(Request $request): View|RedirectResponse
    {
        $this->authorize('offer', ConsultantProfile::class);
        $profile = $request->user()->consultantProfile;
        if (! $profile) {
            return redirect()->route('consulting.profile')->with('status', 'Set up your consultant profile first.');
        }

        $profile->load('rules', 'daysOff');

        return view('consulting::mine.availability', [
            'profile' => $profile,
            'preview' => $this->availability->slots($profile, 7),
            'upcomingDaysOff' => $profile->daysOff->filter(fn ($d) => $d->date->gte(today($profile->timezone)->startOfDay())),
        ]);
    }

    public function storeHours(AvailabilityRuleRequest $request): RedirectResponse
    {
        $profile = $request->user()->consultantProfile ?? abort(404);
        $rule = $this->consultants->addHours($profile, (int) $request->validated('weekday'), $request->validated('starts'), $request->validated('ends'));

        return back()->with('status', 'Added '.$rule->label().'.');
    }

    public function destroyHours(Request $request, AvailabilityRule $rule): RedirectResponse
    {
        abort_unless($rule->consultant_profile_id === $request->user()->consultantProfile?->id, 404);

        $this->consultants->removeHours($rule);

        return back()->with('status', 'Removed '.$rule->label().'.');
    }

    public function storeDayOff(DayOffRequest $request): RedirectResponse
    {
        $profile = $request->user()->consultantProfile ?? abort(404);
        $day = $this->consultants->addDayOff($profile, $request->validated('date'), $request->validated('reason'));

        return back()->with('status', $day->date->format('D j M Y').' is now a day off.');
    }

    public function destroyDayOff(Request $request, AvailabilityException $day): RedirectResponse
    {
        abort_unless($day->consultant_profile_id === $request->user()->consultantProfile?->id, 404);

        $this->consultants->removeDayOff($day);

        return back()->with('status', 'Day off removed.');
    }
}
