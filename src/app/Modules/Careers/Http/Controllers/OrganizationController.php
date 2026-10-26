<?php

namespace App\Modules\Careers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Careers\Http\Requests\OrganizationRequest;
use App\Modules\Careers\Models\JobPost;
use App\Modules\Careers\Models\Organization;
use App\Modules\Careers\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Organization (employer) profiles — roadmap 1.12. */
class OrganizationController extends Controller
{
    public function __construct(private OrganizationService $organizations) {}

    /** Directory of every organization, with how many opportunities are open. */
    public function index(Request $request): View
    {
        $search = is_string($request->query('q')) ? trim($request->query('q')) : '';

        $organizations = Organization::query()
            ->withCount(['jobPosts as open_count' => fn ($q) => $q->open()])
            ->search($search)
            ->orderByDesc('open_count')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('careers::organizations.index', compact('organizations', 'search'));
    }

    public function show(Organization $organization): View
    {
        $organization->load('owner');

        $open = $organization->jobPosts()->open()->with('user')->latest()->get();
        $closedCount = $organization->jobPosts()->count() - $open->count();

        return view('careers::organizations.show', compact('organization', 'open', 'closedCount'));
    }

    public function create(): View
    {
        $this->authorize('create', Organization::class);

        return view('careers::organizations.create');
    }

    public function store(OrganizationRequest $request): RedirectResponse
    {
        $organization = $this->organizations->create($request->user(), $request->validated());

        return redirect()->route('organizations.show', $organization)
            ->with('status', 'Organization profile created. Choose it when you post an opportunity.');
    }

    public function edit(Organization $organization): View
    {
        $this->authorize('update', $organization);

        return view('careers::organizations.edit', compact('organization'));
    }

    public function update(OrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->organizations->update($organization, $request->validated());

        return redirect()->route('organizations.show', $organization)->with('status', 'Organization profile updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        $this->organizations->delete($organization);

        return redirect()->route('jobs.mine')->with('status', 'Organization profile deleted. Its opportunities stay on the board.');
    }
}
