<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Category;
use App\Modules\Knowledge\Http\Requests\LearningPathRequest;
use App\Modules\Knowledge\Http\Requests\LearningResourceRequest;
use App\Modules\Knowledge\Http\Requests\LearningStepRequest;
use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Knowledge\Models\LearningPathStep;
use App\Modules\Knowledge\Models\LearningResource;
use App\Modules\Knowledge\Services\LearningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Curating the Learning Hub (roadmap 2.3): resources, paths and their steps. */
class LearningCurationController extends Controller
{
    public function __construct(private LearningService $learning) {}

    // ---------------------------------------------------------- resources

    public function createResource(): View
    {
        $this->authorize('create', LearningResource::class);

        return view('knowledge::learning.resource-form', ['resource' => null, 'categories' => Category::orderBy('name')->get()]);
    }

    public function storeResource(LearningResourceRequest $request): RedirectResponse
    {
        $this->learning->createResource($request->user(), $request->validated());

        return redirect()->route('learning.resources')->with('status', 'Resource added to the library.');
    }

    public function editResource(LearningResource $resource): View
    {
        $this->authorize('update', $resource);

        return view('knowledge::learning.resource-form', ['resource' => $resource, 'categories' => Category::orderBy('name')->get()]);
    }

    public function updateResource(LearningResourceRequest $request, LearningResource $resource): RedirectResponse
    {
        $this->learning->updateResource($resource, $request->validated());

        return redirect()->route('learning.resources')->with('status', 'Resource updated.');
    }

    public function destroyResource(LearningResource $resource): RedirectResponse
    {
        $this->authorize('delete', $resource);

        $this->learning->deleteResource($resource);

        return redirect()->route('learning.resources')->with('status', 'Resource removed from the library and its paths.');
    }

    // -------------------------------------------------------------- paths

    public function createPath(): View
    {
        $this->authorize('create', LearningPath::class);

        return view('knowledge::learning.path-create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function storePath(LearningPathRequest $request): RedirectResponse
    {
        $path = $this->learning->createPath($request->user(), $request->validated());

        return redirect()->route('learning.paths.edit', $path)->with('status', 'Path created as a draft. Now add its steps.');
    }

    /** Details and the step builder on one page. */
    public function editPath(LearningPath $path): View
    {
        $this->authorize('update', $path);

        $path->load('steps.resource');

        return view('knowledge::learning.path-edit', [
            'path'       => $path,
            'categories' => Category::orderBy('name')->get(),
            'resources'  => LearningResource::whereNotIn('id', $path->steps->pluck('learning_resource_id'))->orderBy('title')->get(),
        ]);
    }

    public function updatePath(LearningPathRequest $request, LearningPath $path): RedirectResponse
    {
        $this->learning->updatePath($path, $request->validated(), $request->input('action') === 'publish');

        return redirect()->route('learning.paths.edit', $path)
            ->with('status', $path->isPublished() ? 'Path published.' : 'Saved as a draft.');
    }

    public function destroyPath(LearningPath $path): RedirectResponse
    {
        $this->authorize('delete', $path);

        $this->learning->deletePath($path);

        return redirect()->route('learning.mine')->with('status', 'Path deleted.');
    }

    // -------------------------------------------------------------- steps

    public function storeStep(LearningStepRequest $request, LearningPath $path): RedirectResponse
    {
        $this->learning->addStep($path, (int) $request->validated('learning_resource_id'), $request->validated('note'));

        return redirect()->to(route('learning.paths.edit', $path).'#steps')->with('status', 'Step added.');
    }

    public function moveStep(Request $request, LearningPathStep $step): RedirectResponse
    {
        $this->authorize('update', $step->path);

        $this->learning->moveStep($step, $request->input('direction') === 'up' ? 'up' : 'down');

        return redirect()->to(route('learning.paths.edit', $step->path).'#steps');
    }

    public function destroyStep(LearningPathStep $step): RedirectResponse
    {
        $this->authorize('update', $step->path);

        $path = $step->path;
        $this->learning->removeStep($step);

        return redirect()->to(route('learning.paths.edit', $path).'#steps')->with('status', 'Step removed.');
    }
}
