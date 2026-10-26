<?php

namespace App\Modules\Governance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Http\Requests\DeleteAccountRequest;
use App\Modules\Governance\Services\PrivacyService;
use App\Modules\Platform\Support\PersonalData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** "Privacy & my data" (roadmap 2.6): consent, download, delete. */
class PrivacyController extends Controller
{
    public function __construct(private PrivacyService $privacy) {}

    public function show(Request $request, PersonalData $registry): View
    {
        return view('governance::privacy.show', [
            'accepted' => $this->privacy->hasAccepted($request->user()),
            'sections' => $registry->sections(),
        ]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $this->privacy->accept($request->user());

        return back()->with('status', 'Thank you — you accepted the current terms and privacy policy.');
    }

    /** Everything we hold about you, as a JSON file. */
    public function export(Request $request): JsonResponse
    {
        return response()->json($this->privacy->export($request->user()), 200, [
            'Content-Disposition' => 'attachment; filename="aqdesk-my-data-'.now()->format('Y-m-d').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        $this->privacy->deleteAccount($request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account has been deleted. Thank you for being part of AQDesk.');
    }
}
