<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Http\Requests\VoteRequest;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Services\VoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * The plain vote forms get a redirect back to the post; the Vue vote buttons
 * (Accept: application/json) get the new score and the voter's own vote, so
 * the page does not reload. A refused vote is a 422 with the reason.
 */
class VoteController extends Controller
{
    public function __construct(private VoteService $votes) {}

    public function question(VoteRequest $request, Question $question): RedirectResponse|JsonResponse
    {
        $this->votes->cast($request->user(), $question, (int) $request->validated('value'));

        return $this->respond($request, $question, route('questions.show', $question));
    }

    public function answer(VoteRequest $request, Answer $answer): RedirectResponse|JsonResponse
    {
        $this->votes->cast($request->user(), $answer, (int) $request->validated('value'));

        return $this->respond($request, $answer, route('questions.show', $answer->question).'#answer-'.$answer->id);
    }

    private function respond(VoteRequest $request, Question|Answer $post, string $redirect): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'score' => $post->score(),
                'vote'  => $post->voteOf($request->user())?->value,
            ]);
        }

        return redirect()->to($redirect);
    }
}
