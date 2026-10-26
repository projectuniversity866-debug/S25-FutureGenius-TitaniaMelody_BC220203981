<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Http\Requests\BookingRequest;
use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Services\AvailabilityService;
use App\Modules\Consulting\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** FR6 "Consultation booking" (roadmap 3.12): pick a free time, say what it is about, book. */
class BookingController extends Controller
{
    public function __construct(private BookingService $bookings, private AvailabilityService $availability) {}

    /** Confirm page for one slot (?start=ISO time from the profile's slot list). */
    public function create(Request $request, ConsultantProfile $consultant): View|RedirectResponse
    {
        $this->authorize('view', $consultant);

        try {
            $start = CarbonImmutable::parse((string) $request->query('start'))->utc();
        } catch (\Throwable) {
            $start = null;
        }
        if (! is_string($request->query('start')) || ! $start || ! $this->availability->isFree($consultant, $start)) {
            return redirect()->to(route('consultants.show', $consultant).'#times')
                ->withErrors(['start' => 'That time is no longer free. Please pick another.']);
        }

        return view('consulting::bookings.create', ['profile' => $consultant->load('user'), 'start' => $start->setTimezone($consultant->timezone)]);
    }

    public function store(BookingRequest $request, ConsultantProfile $consultant): RedirectResponse
    {
        $consultation = $this->bookings->book($consultant, $request->user(), $request->start(), $request->validated('topic'), $request->validated('message'));

        return redirect()->route('consulting.sessions.show', $consultation)->with('status', 'Booked. '.$consultant->user->name.' has been told.');
    }
}
