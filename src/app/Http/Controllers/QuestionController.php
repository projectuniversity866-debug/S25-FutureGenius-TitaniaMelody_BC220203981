<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR2 "Community Q&A System" / prototype module #5.
 */
class QuestionController extends Controller
{
    /** Browsable, searchable, filterable list of questions. */
    public function index(Request $request): View
    {
        $sort = $request->query('sort', 'latest');

        $questions = Question::query()
            ->with(['user', 'category', 'tags', 'votes'])
            ->withCount('answers')
            ->search($request->query('q'))
            ->inCategory($request->query('category'))
            ->withTag($request->query('tag'))
            ->when($sort === 'unanswered', fn ($query) => $query->doesntHave('answers'))
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('views_count'))
            ->when($sort !== 'popular', fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        $categories = Category::withCount('questions')->get();
        $popularTags = Tag::withCount('questions')->orderByDesc('questions_count')->take(15)->get();

        return view('questions.index', compact('questions', 'categories', 'popularTags', 'sort'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('questions.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'min:10', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'body'        => ['required', 'string', 'min:20'],
            'tags'        => ['nullable', 'string', 'max:255'],
        ]);

        $question = Question::create([
            'user_id'     => $request->user()->id,
            'category_id' => $validated['category_id'],
            'title'       => $validated['title'],
            'slug'        => Question::makeSlug($validated['title']),
            'body'        => $validated['body'],
        ]);

        $question->tags()->sync($this->resolveTagIds($validated['tags'] ?? null));

        return redirect()->route('questions.show', $question)
            ->with('status', 'Your question has been posted.');
    }

    public function show(Request $request, Question $question): View
    {
        // Cheap view counter — good enough for a prototype.
        $question->increment('views_count');

        $question->load([
            'user.profile', 'category', 'tags',
            'answers.user.profile', 'answers.votes',
            'votes',
        ]);

        // Highest scoring answer first, accepted answer always on top.
        $answers = $question->answers
            ->sortByDesc(fn ($answer) => [$answer->is_accepted ? 1 : 0, $answer->score()])
            ->values();

        return view('questions.show', compact('question', 'answers'));
    }

    public function edit(Request $request, Question $question): View
    {
        $this->authorizeEdit($request, $question);

        $categories = Category::orderBy('name')->get();

        return view('questions.edit', compact('question', 'categories'));
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeEdit($request, $question);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'min:10', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'body'        => ['required', 'string', 'min:20'],
            'tags'        => ['nullable', 'string', 'max:255'],
        ]);

        $question->update([
            'category_id' => $validated['category_id'],
            'title'       => $validated['title'],
            'slug'        => Question::makeSlug($validated['title'], $question->id),
            'body'        => $validated['body'],
        ]);

        $question->tags()->sync($this->resolveTagIds($validated['tags'] ?? null));

        return redirect()->route('questions.show', $question)
            ->with('status', 'Question updated.');
    }

    public function destroy(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeEdit($request, $question);

        $question->delete();

        return redirect()->route('questions.index')->with('status', 'Question deleted.');
    }

    /** Only the author or an administrator may modify a question. */
    private function authorizeEdit(Request $request, Question $question): void
    {
        $user = $request->user();

        abort_unless($user->id === $question->user_id || $user->canModerate(), 403);
    }

    /**
     * Turn "laravel, php, mysql" into an array of tag IDs, creating
     * any tag that does not exist yet. Capped at 5 tags per question.
     *
     * @return array<int, int>
     */
    private function resolveTagIds(?string $input): array
    {
        if (blank($input)) {
            return [];
        }

        $names = collect(explode(',', $input))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->take(5);

        return $names->map(fn ($name) => Tag::findOrCreateByName($name)->id)->all();
    }
}
