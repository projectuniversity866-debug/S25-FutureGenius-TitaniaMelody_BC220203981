<?php

namespace App\Modules\Consulting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The public consultant directory and profiles (FR6, roadmap 3.11). */
class ConsultantController extends Controller
{
    public function __construct(private AvailabilityService $availability) {}

    public function index(Request $request): View
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        $profiles = ConsultantProfile::query()->where('is_accepting', true)
            ->whereHas('user', fn ($u) => $u->where('is_active', true)
                ->whereHas('role.permissions', fn ($p) => $p->where('slug', 'consulting.offer')))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('headline', 'like', "%{$q}%")->orWhere('expertise', 'like', "%{$q}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))))
            ->with(['user.profile', 'rules', 'daysOff'])->orderBy('headline')->paginate(12)->withQueryString();

        // Soonest free session per consultant (the profile shows the full list).
        $next = $profiles->getCollection()->mapWithKeys(fn (ConsultantProfile $p) => [$p->id => $this->availability->next($p)]);

        return view('consulting::consultants.index', compact('profiles', 'q', 'next'));
    }

    public function show(ConsultantProfile $consultant): View
    {
        $this->authorize('view', $consultant);

        $consultant->load('user.profile', 'rules', 'daysOff');

        return view('consulting::consultants.show', [
            'profile' => $consultant,
            'slots'   => $this->availability->slots($consultant, 14),
        ]);
    }
}
