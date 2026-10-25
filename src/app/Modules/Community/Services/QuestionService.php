<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Events\QuestionAsked;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Models\Tag;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    /** Most tags a question may carry. */
    public const MAX_TAGS = 5;

    public function __construct(
        private ActivityLogger $activity,
        private ReputationService $reputation,
        private PostCleaner $cleaner,
    ) {}

    /**
     * @param  array{title: string, category_id: int, body: string, tags?: ?string}  $data
     */
    public function ask(User $author, array $data): Question
    {
        $question = DB::transaction(function () use ($author, $data) {
            $question = Question::create([
                'user_id'     => $author->id,
                'category_id' => $data['category_id'],
                'title'       => $data['title'],
                'slug'        => Question::makeSlug($data['title']),
                'body'        => $data['body'],
            ]);

            $question->tags()->sync($this->resolveTagIds($data['tags'] ?? null));

            return $question;
        });

        QuestionAsked::dispatch($question);
        $this->reputation->awardBadges($author);

        return $question;
    }

    /**
     * Edits keep the previous version in the history (revisions) when the
     * title or text actually changed.
     *
     * @param  array{title: string, category_id: int, body: string, tags?: ?string}  $data
     */
    public function update(Question $question, array $data, ?User $editor = null): Question
    {
        return DB::transaction(function () use ($question, $data, $editor) {
            if ($question->title !== $data['title'] || $question->body !== $data['body']) {
                $question->revisions()->create([
                    'user_id' => $editor?->id, 'title' => $question->title, 'body' => $question->body,
                ]);
            }

            $question->update([
                'category_id' => $data['category_id'],
                'title'       => $data['title'],
                'slug'        => Question::makeSlug($data['title'], $question->id),
                'body'        => $data['body'],
            ]);

            $question->tags()->sync($this->resolveTagIds($data['tags'] ?? null));

            return $question;
        });
    }

    public function delete(Question $question): void
    {
        $this->activity->record('question.deleted', $question, 'Deleted the question “'.$question->title.'”',
            ['author' => $question->user->name]);

        DB::transaction(function () use ($question) {
            $question->answers->each(fn ($answer) => $this->cleaner->purge($answer));
            $this->cleaner->purge($question);

            $question->delete();   // answers go with it (foreign key cascade)
        });
    }

    public function recordView(Question $question): void
    {
        $question->increment('views_count');
    }

    /** Moderator action: close a question to new answers, or re-open it. */
    public function toggleClosed(Question $question): Question
    {
        $question->update(['is_closed' => ! $question->is_closed]);

        $this->activity->record(
            $question->is_closed ? 'question.closed' : 'question.reopened',
            $question,
            ($question->is_closed ? 'Closed' : 'Re-opened').' the question “'.$question->title.'”',
        );

        return $question;
    }

    /**
     * Turn "laravel, php, mysql" into tag IDs, creating any tag that does not
     * exist yet.
     *
     * @return array<int, int>
     */
    private function resolveTagIds(?string $input): array
    {
        if (blank($input)) {
            return [];
        }

        return collect(explode(',', $input))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->take(self::MAX_TAGS)
            ->map(fn ($name) => Tag::findOrCreateByName($name)->id)
            ->all();
    }
}
