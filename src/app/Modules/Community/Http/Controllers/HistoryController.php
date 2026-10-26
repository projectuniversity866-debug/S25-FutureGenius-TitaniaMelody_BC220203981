<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Platform\Support\LineDiff;
use Illuminate\View\View;

/**
 * Edit history of a question or answer: every version, newest first, with
 * what changed compared with the version before it. Public, like the post.
 */
class HistoryController extends Controller
{
    public function question(Question $question): View
    {
        return $this->show($question, $question, route('questions.show', $question));
    }

    public function answer(Answer $answer): View
    {
        return $this->show($answer, $answer->question, route('questions.show', $answer->question).'#answer-'.$answer->id);
    }

    private function show(Question|Answer $post, Question $question, string $backUrl): View
    {
        $revisions = $post->revisions()->with('user')->get();

        // Versions newest first: the current text, then each saved revision.
        $versions = collect([[
            'title' => $post instanceof Question ? $post->title : null,
            'body'  => $post->body,
            'by'    => $revisions->first()?->user ?? $post->user,
            'at'    => $post->updated_at,
            'current' => true,
        ]])->concat($revisions->map(fn ($r, $i) => [
            'title' => $r->title,
            'body'  => $r->body,
            'by'    => $revisions->get($i + 1)?->user ?? $post->user,
            'at'    => $revisions->get($i + 1)?->created_at ?? $post->created_at,
            'current' => false,
        ]))->values();

        $versions = $versions->map(function ($v, $i) use ($versions) {
            $previous = $versions->get($i + 1);
            $v['diff'] = $previous ? LineDiff::compare($previous['body'], $v['body']) : null;
            $v['titleChanged'] = $previous && $previous['title'] !== $v['title'] ? $previous['title'] : null;

            return $v;
        });

        return view('community::questions.history', [
            'post' => $post, 'question' => $question, 'versions' => $versions, 'backUrl' => $backUrl,
            'kind' => $post instanceof Question ? 'question' : 'answer',
        ]);
    }
}
