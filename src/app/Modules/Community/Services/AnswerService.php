<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Events\AnswerAccepted;
use App\Modules\Community\Events\AnswerPosted;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

class AnswerService
{
    public function post(Question $question, User $author, string $body): Answer
    {
        $answer = $question->answers()->create([
            'user_id' => $author->id,
            'body'    => $body,
        ]);

        AnswerPosted::dispatch($answer);

        return $answer;
    }

    public function update(Answer $answer, string $body): Answer
    {
        $answer->update(['body' => $body]);

        return $answer;
    }

    public function delete(Answer $answer): void
    {
        $answer->delete();
    }

    /** A question has at most one accepted answer. */
    public function accept(Answer $answer): void
    {
        if ($answer->is_accepted) {
            return;
        }

        DB::transaction(function () use ($answer) {
            $answer->question->answers()->update(['is_accepted' => false]);
            $answer->update(['is_accepted' => true]);
        });

        AnswerAccepted::dispatch($answer);
    }
}
