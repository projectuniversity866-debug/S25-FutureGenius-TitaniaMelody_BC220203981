<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Http\Requests\CancelConsultationRequest;
use App\Modules\Consulting\Models\Consultation;
use App\Modules\Consulting\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "My sessions": the ones you booked, and — for consultants — the ones booked with you (roadmap 3.12). */
class SessionController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->consultantProfile;
        $side = $profile && $request->query('side') === 'clients' ? 'clients' : 'mine';
        $tab = in_array($request->query('tab'), ['past', 'cancelled'], true) ? $request->query('tab') : 'upcoming';

        $base = fn () => $side === 'clients'
            ? Consultation::where('consultant_profile_id', $profile->id)
            : Consultation::where('client_id', $user->id);
        $filters = [
            'upcoming'  => fn ($q) => $q->where('status', 'confirmed')->where('ends_at', '>=', now())->orderBy('starts_at'),
            'past'      => fn ($q) => $q->where('status', '!=', 'cancelled')->where('ends_at', '<', now())->orderByDesc('starts_at'),
            'cancelled' => fn ($q) => $q->where('status', 'cancelled')->orderByDesc('starts_at'),
        ];

        $sessions = $filters[$tab]($base())->with('profile.user', 'client')->paginate(15)->withQueryString();
        $counts = collect($filters)->map(fn ($f) => $f($base())->count());

        return view('consulting::sessions.index', compact('sessions', 'tab', 'side', 'counts', 'profile'));
    }

    public function show(Consultation $consultation): View
    {
        $this->authorize('view', $consultation);

        return view('consulting::sessions.show', ['consultation' => $consultation->load('profile.user', 'client', 'canceller', 'clientRecord')]);
    }

    public function cancel(CancelConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->bookings->cancel($consultation, $request->user(), $request->validated('reason'));

        return back()->with('status', 'Session cancelled; the other side has been told and the time is free again.');
    }

    public function complete(Consultation $consultation): RedirectResponse
    {
        $this->authorize('complete', $consultation);

        $this->bookings->complete($consultation);

        return back()->with('status', 'Marked as done.');
    }
}
