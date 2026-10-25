<?php

namespace App\Modules\Careers\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Services\JobPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administrator moderation of published opportunities. The whole controller
 * is behind the can:content.moderate route middleware.
 */
class OpportunityModerationController extends Controller
{
    public function __construct(private JobPostService $jobs) {}

    public function index(Request $request): View
    {
        $jobs = JobPost::with('user')
            ->withCount('applications')
            ->when($request->query('q'), fn ($query, $term) => $query->search($term))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('careers::admin.jobs.index', compact('jobs'));
    }

    public function toggle(JobPost $job): RedirectResponse
    {
        $this->jobs->togglePublished($job);

        return back()->with('status', 'Opportunity '.($job->is_active ? 'published' : 'unpublished').'.');
    }

    public function destroy(JobPost $job): RedirectResponse
    {
        $this->jobs->delete($job);

        return back()->with('status', 'Opportunity deleted.');
    }
}
