<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Knowledge\Http\Requests\VerificationRequest;
use App\Modules\Knowledge\Models\ExpertVerification;
use App\Modules\Knowledge\Services\ExpertVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The member's side of expert verification (roadmap 2.1). */
class VerificationController extends Controller
{
    public function __construct(private ExpertVerificationService $verification) {}

    /** Current status, and the application form when one may be sent. */
    public function show(Request $request): View
    {
        $latest = $this->verification->latestFor($request->user());

        return view('knowledge::verification.show', compact('latest'));
    }

    public function store(VerificationRequest $request): RedirectResponse
    {
        $this->verification->apply($request->user(), $request->validated(), $request->file('document'));

        return redirect()->route('verification.show')
            ->with('status', 'Request sent. An administrator will review it and you will be notified.');
    }

    /** The evidence file — private, sent only after the policy check. */
    public function document(ExpertVerification $verification): StreamedResponse
    {
        $this->authorize('viewDocument', $verification);

        return $this->verification->downloadDocument($verification);
    }
}
