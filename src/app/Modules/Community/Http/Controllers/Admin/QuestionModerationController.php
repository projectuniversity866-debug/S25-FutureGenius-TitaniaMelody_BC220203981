<?php

namespace App\Modules\Community\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administrator moderation of community questions. The whole controller is
 * behind the can:content.moderate route middleware.
 */
class QuestionModerationController extends Controller
{
    public function __construct(private QuestionService $questions) {}

    public function index(Request $request): View
    {
        $questions = Question::with(['user', 'category'])
            ->withCount('answers')
            ->when($request->query('q'), fn ($query, $term) => $query->search($term))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('community::admin.questions.index', compact('questions'));
    }

    public function toggle(Question $question): RedirectResponse
    {
        $this->questions->toggleClosed($question);

        return back()->with('status', 'Question '.($question->is_closed ? 'closed' : 're-opened').'.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->questions->delete($question);

        return back()->with('status', 'Question deleted.');
    }
}
