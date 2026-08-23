<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Upvote answers" from FR2. One route handles questions and answers via a
 * polymorphic relation, so there is a single place to reason about voting.
 */
class VoteController extends Controller
{
    public function question(Request $request, Question $question): RedirectResponse
    {
        return $this->cast($request, $question);
    }

    public function answer(Request $request, Answer $answer): RedirectResponse
    {
        return $this->cast($request, $answer, $answer->question);
    }

    private function cast(Request $request, Model $votable, ?Question $redirectTo = null): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'integer', 'in:1,-1'],
        ]);

        $user  = $request->user();
        $value = (int) $validated['value'];

        // A user cannot vote on their own content.
        if ($votable->user_id === $user->id) {
            return back()->withErrors(['vote' => 'You cannot vote on your own post.']);
        }

        $existing = $votable->votes()->where('user_id', $user->id)->first();

        if ($existing && $existing->value === $value) {
            $existing->delete();                       // clicking again removes the vote
        } elseif ($existing) {
            $existing->update(['value' => $value]);    // switch upvote <-> downvote
        } else {
            $votable->votes()->create([
                'user_id' => $user->id,
                'value'   => $value,
            ]);
        }

        $question = $redirectTo ?: $votable;

        return redirect()->route('questions.show', $question);
    }
}
