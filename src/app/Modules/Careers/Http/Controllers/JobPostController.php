<?php

namespace App\Modules\Careers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Careers\Http\Requests\JobPostRequest;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Services\JobPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR5 "Jobs and Opportunities Board" — browsing and publishing opportunities.
 */
class JobPostController extends Controller
{
    public function __construct(private JobPostService $jobs) {}

    public function index(Request $request): View
    {
        $jobs = JobPost::query()
            ->with('user')
            ->withCount('applications')
            ->active()
            ->search(is_string($request->query('q')) ? $request->query('q') : null)
            ->ofType($request->query('type'))
            ->ofWorkMode($request->query('mode'))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        // One query for every type's count; types with none are 0.
        $byType = JobPost::active()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $counts = ['all' => (int) $byType->sum()];
        foreach (array_keys(JobPost::TYPES) as $key) {
            $counts[$key] = (int) ($byType[$key] ?? 0);
        }

        return view('careers::jobs.index', compact('jobs', 'counts'));
    }

    public function show(Request $request, JobPost $job): View
    {
        $job->load('user.profile', 'organization')->loadCount('applications');

        $hasApplied = $job->hasApplicationFrom($request->user());

        return view('careers::jobs.show', compact('job', 'hasApplied'));
    }

    public function create(Request $request): View
    {
        $organizations = $request->user()->organizations()->orderBy('name')->get();

        return view('careers::jobs.create', compact('organizations'));
    }

    public function store(JobPostRequest $request): RedirectResponse
    {
        $job = $this->jobs->publish($request->user(), $request->validated());

        return redirect()->route('jobs.show', $job)
            ->with('status', 'Opportunity published.');
    }

    public function edit(JobPost $job): View
    {
        $this->authorize('update', $job);

        // The poster's organizations, also when a moderator edits the post.
        $organizations = $job->user->organizations()->orderBy('name')->get();

        return view('careers::jobs.edit', compact('job', 'organizations'));
    }

    public function update(JobPostRequest $request, JobPost $job): RedirectResponse
    {
        $this->jobs->update($job, $request->validated(), $request->boolean('is_active'));

        return redirect()->route('jobs.show', $job)->with('status', 'Opportunity updated.');
    }

    public function destroy(JobPost $job): RedirectResponse
    {
        $this->authorize('delete', $job);

        $this->jobs->delete($job);

        return redirect()->route('jobs.index')->with('status', 'Opportunity removed.');
    }

    /** Opportunities published by the signed-in user, with their applicants. */
    public function mine(Request $request): View
    {
        $jobs = $request->user()
            ->jobPosts()
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        $organizations = $request->user()->organizations()
            ->withCount(['jobPosts as open_count' => fn ($q) => $q->open()])
            ->orderBy('name')->get();

        return view('careers::jobs.mine', compact('jobs', 'organizations'));
    }

    /** Applicant list for one opportunity, optionally narrowed to one status. */
    public function applicants(Request $request, JobPost $job): View
    {
        $this->authorize('viewApplicants', $job);

        $status = in_array($request->query('status'), JobApplication::STATUSES, true)
            ? $request->query('status')
            : null;

        $applications = $job->applications()
            ->with('user.profile')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statusCounts = $job->applications()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('careers::jobs.applicants', compact('job', 'applications', 'status', 'statusCounts'));
    }
}
