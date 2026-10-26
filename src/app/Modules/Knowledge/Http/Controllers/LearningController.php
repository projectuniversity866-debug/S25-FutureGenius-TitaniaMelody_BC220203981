<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Category;
use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningPathStep;
use App\Modules\Knowledge\Models\LearningResource;
use App\Modules\Knowledge\Services\LearningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Learning Hub for learners (roadmap 2.3): paths, the library, progress. */
class LearningController extends Controller
{
    public function __construct(private LearningService $learning) {}

    public function index(Request $request): View
    {
        $category = is_string($request->query('category')) ? $request->query('category') : null;

        $paths = LearningPath::published()
            ->with('category', 'curator')
            ->withCount('steps')
            ->when($category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $category)))
            ->latest('published_at')
            ->get();

        $categories = Category::orderBy('name')->get();
        $started = $request->user() ? $this->learning->startedBy($request->user())->keyBy(fn ($p) => $p['path']->id) : collect();
        $resourceCount = LearningResource::count();

        return view('knowledge::learning.index', compact('paths', 'categories', 'category', 'started', 'resourceCount'));
    }

    /** The resource library, filterable like the other boards. */
    public function resources(Request $request): View
    {
        $text = fn (string $key) => is_string($request->query($key)) ? $request->query($key) : null;

        $resources = LearningResource::query()
            ->with('category')
            ->search($text('q'))
            ->when($text('category'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when(isset(LearningResource::KINDS[$text('kind') ?? '']), fn ($q) => $q->where('kind', $text('kind')))
            ->when(isset(LearningResource::LEVELS[$text('level') ?? '']), fn ($q) => $q->where('level', $text('level')))
            ->when($request->query('free') === '1', fn ($q) => $q->where('is_free', true))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('knowledge::learning.resources', [
            'resources'  => $resources,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, LearningPath $path): View
    {
        $this->authorize('view', $path);

        $path->load('category', 'curator.profile', 'steps.resource');
        $done = $this->learning->completedStepIds($request->user(), $path);

        return view('knowledge::learning.path', compact('path', 'done'));
    }

    /** Tick or untick a step (signed-in learners, published paths). */
    public function toggle(Request $request, LearningPathStep $step): RedirectResponse
    {
        $this->authorize('view', $step->path);

        $done = $this->learning->toggleStep($request->user(), $step);

        return redirect()->to(route('learning.paths.show', $step->path).'#step-'.$step->id)
            ->with('status', $done ? 'Step marked as done.' : 'Step marked as not done.');
    }

    /** My learning: paths in progress, and what I curate. */
    public function mine(Request $request): View
    {
        $user = $request->user();

        return view('knowledge::learning.mine', [
            'started'   => $this->learning->startedBy($user),
            'paths'     => LearningPath::where('user_id', $user->id)->withCount('steps')->latest('updated_at')->get(),
            'resources' => LearningResource::where('user_id', $user->id)->latest()->get(),
        ]);
    }
}
