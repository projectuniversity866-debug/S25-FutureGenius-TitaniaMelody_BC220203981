<?php

namespace App\Modules\Knowledge\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Knowledge\Http\Requests\ReviewVerificationRequest;
use App\Modules\Knowledge\Models\ExpertVerification;
use App\Modules\Knowledge\Services\ExpertVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Administrators review expert verification requests (roadmap 2.1). */
class VerificationReviewController extends Controller
{
    public function __construct(private ExpertVerificationService $verification) {}

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ExpertVerification::STATUSES, true) ? $request->query('status') : 'pending';

        $requests = ExpertVerification::with('user.profile', 'reviewer')
            ->where('status', $status)
            ->when($status === 'pending', fn ($q) => $q->oldest(), fn ($q) => $q->latest('reviewed_at'))
            ->paginate(15)
            ->withQueryString();

        $counts = ExpertVerification::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('knowledge::admin.verifications', compact('requests', 'status', 'counts'));
    }

    public function show(ExpertVerification $verification): View
    {
        $this->authorize('review', $verification);

        $verification->load('user.profile', 'reviewer');
        $history = ExpertVerification::where('user_id', $verification->user_id)->whereKeyNot($verification->id)->latest()->get();

        return view('knowledge::admin.verification', compact('verification', 'history'));
    }

    public function decide(ReviewVerificationRequest $request, ExpertVerification $verification): RedirectResponse
    {
        $note = $request->validated('review_note');

        $request->validated('decision') === 'approve'
            ? $this->verification->approve($verification, $request->user(), $note)
            : $this->verification->reject($verification, $request->user(), $note);

        return redirect()->route('admin.verifications.index')
            ->with('status', $verification->user->name.'’s request was '.$verification->status.'.');
    }
}
