<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    public function store(Request $request, Question $question): RedirectResponse
    {
        abort_if($question->is_closed, 403, 'This question is closed for new answers.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:10'],
        ]);

        $question->answers()->create([
            'user_id' => $request->user()->id,
            'body'    => $validated['body'],
        ]);

        return redirect()->route('questions.show', $question)
            ->with('status', 'Your answer has been posted.');
    }

    public function update(Request $request, Answer $answer): RedirectResponse
    {
        $this->authorizeEdit($request, $answer);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:10'],
        ]);

        $answer->update($validated);

        return redirect()->route('questions.show', $answer->question)
            ->with('status', 'Answer updated.');
    }

    public function destroy(Request $request, Answer $answer): RedirectResponse
    {
        $this->authorizeEdit($request, $answer);

        $question = $answer->question;
        $answer->delete();

        return redirect()->route('questions.show', $question)->with('status', 'Answer deleted.');
    }

    /** The question's author (or an admin) marks one answer as accepted. */
    public function accept(Request $request, Answer $answer): RedirectResponse
    {
        $question = $answer->question;
        $user     = $request->user();

        abort_unless($user->id === $question->user_id || $user->canModerate(), 403);

        // Only one accepted answer per question.
        $question->answers()->update(['is_accepted' => false]);
        $answer->update(['is_accepted' => true]);

        return redirect()->route('questions.show', $question)
            ->with('status', 'Answer marked as accepted.');
    }

    private function authorizeEdit(Request $request, Answer $answer): void
    {
        $user = $request->user();

        abort_unless($user->id === $answer->user_id || $user->canModerate(), 403);
    }
}
