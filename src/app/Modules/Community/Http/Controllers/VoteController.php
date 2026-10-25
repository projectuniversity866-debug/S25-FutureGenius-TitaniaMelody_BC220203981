<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Http\Requests\VoteRequest;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Services\VoteService;
use Illuminate\Http\RedirectResponse;

class VoteController extends Controller
{
    public function __construct(private VoteService $votes) {}

    public function question(VoteRequest $request, Question $question): RedirectResponse
    {
        $this->votes->cast($request->user(), $question, (int) $request->validated('value'));

        return redirect()->route('questions.show', $question);
    }

    public function answer(VoteRequest $request, Answer $answer): RedirectResponse
    {
        $this->votes->cast($request->user(), $answer, (int) $request->validated('value'));

        return redirect()->route('questions.show', $answer->question);
    }
}
