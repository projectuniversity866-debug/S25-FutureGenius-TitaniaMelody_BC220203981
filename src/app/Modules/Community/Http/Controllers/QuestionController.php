<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Http\Requests\QuestionRequest;
use App\Modules\Community\Models\Category;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Models\Tag;
use App\Modules\Community\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR2 "Community Q&A System" — asking and browsing questions.
 */
class QuestionController extends Controller
{
    public function __construct(private QuestionService $questions) {}

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

        return view('community::questions.index', compact('questions', 'categories', 'popularTags', 'sort'));
    }

    public function create(): View
    {
        $this->authorize('questions.post');

        $categories = Category::orderBy('name')->get();

        return view('community::questions.create', compact('categories'));
    }

    public function store(QuestionRequest $request): RedirectResponse
    {
        $question = $this->questions->ask($request->user(), $request->validated());

        return redirect()->route('questions.show', $question)
            ->with('status', 'Your question has been posted.');
    }

    public function show(Question $question): View
    {
        $this->questions->recordView($question);

        $question->load([
            'user.profile', 'category', 'tags',
            'answers.user.profile', 'answers.votes',
            'votes',
        ]);

        // Highest scoring answer first, accepted answer always on top.
        $answers = $question->answers
            ->sortByDesc(fn ($answer) => [$answer->is_accepted ? 1 : 0, $answer->score()])
            ->values();

        return view('community::questions.show', compact('question', 'answers'));
    }

    public function edit(Question $question): View
    {
        $this->authorize('update', $question);

        $categories = Category::orderBy('name')->get();

        return view('community::questions.edit', compact('question', 'categories'));
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $this->questions->update($question, $request->validated());

        return redirect()->route('questions.show', $question)
            ->with('status', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $this->questions->delete($question);

        return redirect()->route('questions.index')->with('status', 'Question deleted.');
    }
}
