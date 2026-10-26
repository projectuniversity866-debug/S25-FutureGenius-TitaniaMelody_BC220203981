<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Http\Requests\QuestionRequest;
use App\Modules\Community\Models\Category;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Models\Tag;
use App\Modules\Community\Services\QuestionFeed;
use App\Modules\Community\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR2 "Community Q&A System" — asking and browsing questions.
 */
class QuestionController extends Controller
{
    public function __construct(private QuestionService $questions, private QuestionFeed $feed) {}

    /** Browsable, searchable, filterable list of questions. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $sort = $this->feed->sortKey($request->query('sort'));
        $filter = fn (string $key) => is_string($value = $request->query($key)) ? $value : null;
        $following = $user && $request->query('following') === '1';

        $query = $this->feed->listing($user)
            ->search($filter('q'))
            ->inCategory($filter('category'))
            ->withTag($filter('tag'))
            ->when($following, fn ($query) => $query->inTagsFollowedBy($user));

        $questions = $this->feed->sort($query, $sort)->paginate(10)->withQueryString();

        $categories = Category::withCount('questions')->get();
        $popularTags = Tag::withCount('questions')->orderByDesc('questions_count')->take(15)->get();
        $followedTags = $user ? $user->followedTags()->withCount('questions')->orderBy('name')->get() : collect();

        // Filtering by one tag shows that tag's header with a Follow button.
        $currentTag = $filter('tag')
            ? Tag::where('slug', $filter('tag'))->withCount(['questions', 'followers'])->first()
            : null;

        return view('community::questions.index', compact(
            'questions', 'categories', 'popularTags', 'followedTags', 'currentTag', 'sort', 'following',
        ));
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

    public function show(Request $request, Question $question): View
    {
        $this->questions->recordView($question);

        $question->load([
            'user.profile', 'category',
            'tags' => fn ($q) => $q->withCount('followers'),
            'answers' => fn ($q) => $q->withCount('revisions'),
            'answers.user.profile', 'answers.votes', 'answers.comments.user',
            'votes', 'comments.user',
        ])->loadCount(['revisions', 'bookmarks']);

        // Highest scoring answer first, accepted answer always on top.
        $answers = $question->answers
            ->sortByDesc(fn ($answer) => [$answer->is_accepted ? 1 : 0, $answer->score()])
            ->values();

        $user = $request->user();
        $saved = $question->isSavedBy($user);
        $followedTagIds = $user ? $user->followedTags()->pluck('tags.id')->map('intval')->all() : [];
        $related = $this->feed->related($question);

        return view('community::questions.show', compact('question', 'answers', 'saved', 'followedTagIds', 'related'));
    }

    public function edit(Question $question): View
    {
        $this->authorize('update', $question);

        $categories = Category::orderBy('name')->get();

        return view('community::questions.edit', compact('question', 'categories'));
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $this->questions->update($question, $request->validated(), $request->user());

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
