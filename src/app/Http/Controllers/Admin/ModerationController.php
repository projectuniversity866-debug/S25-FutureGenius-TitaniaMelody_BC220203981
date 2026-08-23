<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administrator content moderation for the two live community modules.
 */
class ModerationController extends Controller
{
    public function questions(Request $request): View
    {
        $questions = Question::with(['user', 'category'])
            ->withCount('answers')
            ->when($request->query('q'), fn ($query, $term) => $query->search($term))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.questions.index', compact('questions'));
    }

    public function toggleQuestion(Question $question): RedirectResponse
    {
        $question->update(['is_closed' => ! $question->is_closed]);

        return back()->with('status', 'Question '.($question->is_closed ? 'closed' : 're-opened').'.');
    }

    public function destroyQuestion(Question $question): RedirectResponse
    {
        $question->delete();

        return back()->with('status', 'Question deleted.');
    }

    public function jobs(Request $request): View
    {
        $jobs = JobPost::with('user')
            ->withCount('applications')
            ->when($request->query('q'), fn ($query, $term) => $query->search($term))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.jobs.index', compact('jobs'));
    }

    public function toggleJob(JobPost $job): RedirectResponse
    {
        $job->update(['is_active' => ! $job->is_active]);

        return back()->with('status', 'Opportunity '.($job->is_active ? 'published' : 'unpublished').'.');
    }

    public function destroyJob(JobPost $job): RedirectResponse
    {
        $job->delete();

        return back()->with('status', 'Opportunity deleted.');
    }
}
