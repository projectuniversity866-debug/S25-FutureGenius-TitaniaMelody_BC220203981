<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Http\Requests\AnswerRequest;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Services\AnswerService;
use Illuminate\Http\RedirectResponse;

class AnswerController extends Controller
{
    public function __construct(private AnswerService $answers) {}

    public function store(AnswerRequest $request, Question $question): RedirectResponse
    {
        $this->answers->post($question, $request->user(), $request->validated('body'));

        return redirect()->route('questions.show', $question)
            ->with('status', 'Your answer has been posted.');
    }

    public function update(AnswerRequest $request, Answer $answer): RedirectResponse
    {
        $this->answers->update($answer, $request->validated('body'));

        return redirect()->route('questions.show', $answer->question)
            ->with('status', 'Answer updated.');
    }

    public function destroy(Answer $answer): RedirectResponse
    {
        $this->authorize('delete', $answer);

        $question = $answer->question;
        $this->answers->delete($answer);

        return redirect()->route('questions.show', $question)->with('status', 'Answer deleted.');
    }

    public function accept(Answer $answer): RedirectResponse
    {
        $this->authorize('accept', $answer);

        $this->answers->accept($answer);

        return redirect()->route('questions.show', $answer->question)
            ->with('status', 'Answer marked as accepted.');
    }
}
