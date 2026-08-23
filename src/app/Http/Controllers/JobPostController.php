<?php

namespace App\Http\Controllers;

use App\Models\JobPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR5 "Jobs and Opportunities Board" / prototype module #6.
 */
class JobPostController extends Controller
{
    public function index(Request $request): View
    {
        $jobs = JobPost::query()
            ->with('user')
            ->withCount('applications')
            ->active()
            ->search($request->query('q'))
            ->ofType($request->query('type'))
            ->ofWorkMode($request->query('mode'))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $counts = [
            'all'         => JobPost::active()->count(),
            'job'         => JobPost::active()->where('type', 'job')->count(),
            'internship'  => JobPost::active()->where('type', 'internship')->count(),
        ];

        return view('jobs.index', compact('jobs', 'counts'));
    }

    public function show(Request $request, JobPost $job): View
    {
        $job->load('user.profile')->loadCount('applications');

        $hasApplied = $job->hasApplicationFrom($request->user());

        return view('jobs.show', compact('job', 'hasApplied'));
    }

    public function create(): View
    {
        return view('jobs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateJob($request);

        $job = JobPost::create($validated + [
            'user_id' => $request->user()->id,
            'slug'    => JobPost::makeSlug($validated['title']),
        ]);

        return redirect()->route('jobs.show', $job)
            ->with('status', 'Opportunity published.');
    }

    public function edit(Request $request, JobPost $job): View
    {
        $this->authorizeManage($request, $job);

        return view('jobs.edit', compact('job'));
    }

    public function update(Request $request, JobPost $job): RedirectResponse
    {
        $this->authorizeManage($request, $job);

        $validated = $this->validateJob($request);

        $job->update($validated + [
            'slug'      => JobPost::makeSlug($validated['title'], $job->id),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('jobs.show', $job)->with('status', 'Opportunity updated.');
    }

    public function destroy(Request $request, JobPost $job): RedirectResponse
    {
        $this->authorizeManage($request, $job);

        $job->delete();

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

        return view('jobs.mine', compact('jobs'));
    }

    /** Applicant list for one opportunity (poster or admin only). */
    public function applicants(Request $request, JobPost $job): View
    {
        $this->authorizeManage($request, $job);

        $applications = $job->applications()
            ->with('user.profile')
            ->latest()
            ->paginate(15);

        return view('jobs.applicants', compact('job', 'applications'));
    }

    /** @return array<string, mixed> */
    private function validateJob(Request $request): array
    {
        return $request->validate([
            'title'        => ['required', 'string', 'min:5', 'max:255'],
            'company'      => ['required', 'string', 'max:255'],
            'type'         => ['required', 'in:job,internship'],
            'work_mode'    => ['required', 'in:onsite,remote,hybrid'],
            'location'     => ['nullable', 'string', 'max:255'],
            'description'  => ['required', 'string', 'min:20'],
            'requirements' => ['nullable', 'string'],
            'salary_min'   => ['nullable', 'numeric', 'min:0'],
            'salary_max'   => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'deadline'     => ['nullable', 'date', 'after_or_equal:today'],
        ]);
    }

    /** The poster or an administrator may manage an opportunity. */
    private function authorizeManage(Request $request, JobPost $job): void
    {
        $user = $request->user();

        abort_unless($user->id === $job->user_id || $user->canModerate(), 403);
    }
}
