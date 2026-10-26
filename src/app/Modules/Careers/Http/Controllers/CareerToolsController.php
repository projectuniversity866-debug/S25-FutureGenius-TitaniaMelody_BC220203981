<?php

namespace App\Modules\Careers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Careers\Http\Requests\JobAlertRequest;
use App\Modules\Careers\Models\JobAlert;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Services\CareerToolsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Career tools (roadmap 1.15): save an opportunity for later, and job
 * alerts. Saving answers JSON for the Vue button, a redirect for the form.
 */
class CareerToolsController extends Controller
{
    public function __construct(private CareerToolsService $tools) {}

    public function save(Request $request, JobPost $job): RedirectResponse|JsonResponse
    {
        $this->tools->save($request->user(), $job);

        return $this->respond($request, $job, true, 'Opportunity saved. You can find it under Saved.');
    }

    public function unsave(Request $request, JobPost $job): RedirectResponse|JsonResponse
    {
        $this->tools->unsave($request->user(), $job);

        return $this->respond($request, $job, false, 'Removed from your saved opportunities.');
    }

    public function alerts(Request $request): View
    {
        $alerts = JobAlert::ownedBy($request->user())->latest()->get();

        return view('careers::alerts.index', compact('alerts'));
    }

    public function storeAlert(JobAlertRequest $request): RedirectResponse
    {
        $alert = $this->tools->createAlert($request->user(), $request->validated());

        return redirect()->route('alerts.index')
            ->with('status', 'Alert created: '.$alert->describe().'. You will be notified when a match is published.');
    }

    public function destroyAlert(JobAlert $alert): RedirectResponse
    {
        $this->authorize('delete', $alert);

        $this->tools->deleteAlert($alert);

        return back()->with('status', 'Alert deleted.');
    }

    private function respond(Request $request, JobPost $job, bool $saved, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved, 'count' => $job->savedBy()->count(), 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
