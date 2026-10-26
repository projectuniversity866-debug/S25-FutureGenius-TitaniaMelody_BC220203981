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
    public function __construct(private ReputationService $reputation, private PostCleaner $cleaner) {}

    public function post(Question $question, User $author, string $body): Answer
    {
        $answer = $question->answers()->create([
            'user_id' => $author->id,
            'body'    => $body,
        ]);

        AnswerPosted::dispatch($answer);
        $this->reputation->awardBadges($author);

        return $answer;
    }

    /** The previous text is kept in the answer's history. */
    public function update(Answer $answer, string $body, ?User $editor = null): Answer
    {
        DB::transaction(function () use ($answer, $body, $editor) {
            if ($answer->body !== $body) {
                $answer->revisions()->create(['user_id' => $editor?->id, 'body' => $answer->body]);
            }

            $answer->update(['body' => $body]);
        });

        return $answer;
    }

    public function delete(Answer $answer): void
    {
        DB::transaction(function () use ($answer) {
            $this->cleaner->purge($answer);
            $answer->delete();
        });
    }

    /** A question has at most one accepted answer; its author gains reputation. */
    public function accept(Answer $answer): void
    {
        if ($answer->is_accepted) {
            return;
        }

        DB::transaction(function () use ($answer) {
            $answer->question->answers()->update(['is_accepted' => false]);
            $answer->update(['is_accepted' => true]);
            $this->reputation->recordAcceptance($answer);
        });

        AnswerAccepted::dispatch($answer);
    }
}
