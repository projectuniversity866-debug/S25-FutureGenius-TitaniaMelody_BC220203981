<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Http\Requests\CommentRequest;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Comment;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Services\CommentService;
use Illuminate\Http\RedirectResponse;

/** Short comments under questions and answers (roadmap 1.8). */
class CommentController extends Controller
{
    public function __construct(private CommentService $comments) {}

    public function storeOnQuestion(CommentRequest $request, Question $question): RedirectResponse
    {
        $comment = $this->comments->add($question, $request->user(), $request->validated('body'));

        return redirect()->to(route('questions.show', $question).'#comment-'.$comment->id)
            ->with('status', 'Comment added.');
    }

    public function storeOnAnswer(CommentRequest $request, Answer $answer): RedirectResponse
    {
        $comment = $this->comments->add($answer, $request->user(), $request->validated('body'));

        return redirect()->to(route('questions.show', $answer->question).'#comment-'.$comment->id)
            ->with('status', 'Comment added.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $question = $comment->question();
        $this->comments->delete($comment);

        return redirect()->route('questions.show', $question)->with('status', 'Comment deleted.');
    }
}
