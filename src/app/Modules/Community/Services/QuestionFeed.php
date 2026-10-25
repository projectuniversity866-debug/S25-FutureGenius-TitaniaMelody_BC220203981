<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Comment;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Models\Vote;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read side of Q&A discovery (roadmap 1.10): the sort orders of the
 * questions list, the "trending" score, related questions and a member's
 * saved questions. Read-only, so controllers call it directly.
 */
class QuestionFeed
{
    /** Sort key => tab label. The first one is the default. */
    public const SORTS = [
        'latest'     => 'Newest',
        'trending'   => 'Trending',
        'votes'      => 'Most votes',
        'unanswered' => 'Unanswered',
    ];

    /** Trending counts this many days of activity. */
    public const TRENDING_DAYS = 7;

    /** Weight of each kind of recent activity in the trending score. */
    public const TRENDING_WEIGHTS = ['answers' => 3, 'upvotes' => 2, 'comments' => 1];

    /** An unknown or missing sort falls back to the default. */
    public function sortKey(mixed $sort): string
    {
        return is_string($sort) && array_key_exists($sort, self::SORTS) ? $sort : array_key_first(self::SORTS);
    }

    /**
     * The list query with what every row shows: author, category, tags,
     * score, answer count, accepted answer and, for a signed-in viewer,
     * whether they saved it.
     */
    public function listing(?User $viewer = null): Builder
    {
        return Question::query()
            ->select('questions.*')
            ->with(['user', 'category', 'tags', 'votes'])
            ->withCount('answers')
            ->withExists(['answers as has_accepted_answer' => fn (Builder $q) => $q->where('is_accepted', true)])
            ->when($viewer, fn (Builder $q) => $q->withExists([
                'bookmarks as is_saved' => fn (Builder $b) => $b->where('user_id', $viewer->id),
            ]));
    }

    /** Apply a sort; ties always fall back to newest first. */
    public function sort(Builder $query, string $sort): Builder
    {
        match ($sort) {
            'trending'   => $this->orderByTrending($query),
            'votes'      => $query->addSelect(['vote_score' => $this->scoreSubquery()])->orderByDesc('vote_score'),
            'unanswered' => $query->doesntHave('answers'),
            default      => null,
        };

        return $query->latest('questions.created_at')->orderByDesc('questions.id');
    }

    /**
     * Questions that share the most tags with this one, then questions from
     * the same category, newest first.
     */
    public function related(Question $question, int $limit = 5): Collection
    {
        $base = fn () => Question::query()
            ->whereKeyNot($question->id)
            ->withCount('answers')
            ->withExists(['answers as has_accepted_answer' => fn (Builder $q) => $q->where('is_accepted', true)]);

        $tagIds = $question->tags->modelKeys();

        $related = $tagIds === [] ? new Collection : $base()
            ->whereHas('tags', fn (Builder $q) => $q->whereIn('tags.id', $tagIds))
            ->withCount(['tags as shared_tags_count' => fn (Builder $q) => $q->whereIn('tags.id', $tagIds)])
            ->orderByDesc('shared_tags_count')
            ->latest()
            ->take($limit)
            ->get();

        if ($related->count() < $limit && $question->category_id) {
            $related = $related->concat($base()
                ->where('category_id', $question->category_id)
                ->whereKeyNot($related->modelKeys())
                ->latest()
                ->take($limit - $related->count())
                ->get());
        }

        return $related;
    }

    /** The member's saved questions, most recently saved first. */
    public function savedBy(User $user): Builder
    {
        return $this->listing()
            ->addSelect('bookmarks.created_at as saved_at')
            ->withCasts(['saved_at' => 'datetime'])
            ->join('bookmarks', fn ($join) => $join
                ->on('bookmarks.bookmarkable_id', '=', 'questions.id')
                ->where('bookmarks.bookmarkable_type', (new Question)->getMorphClass())
                ->where('bookmarks.user_id', $user->id))
            ->orderByDesc('bookmarks.created_at')
            ->orderByDesc('bookmarks.id');
    }

    /**
     * Trending = activity in the last TRENDING_DAYS days: new answers, upvotes
     * and comments on the question and its answers, weighted. Questions with
     * no recent activity are left out.
     */
    private function orderByTrending(Builder $query): void
    {
        $since = now()->subDays(self::TRENDING_DAYS);
        $question = (new Question)->getMorphClass();
        $answer = (new Answer)->getMorphClass();
        $w = self::TRENDING_WEIGHTS;

        $parts = [
            [$w['answers'], Answer::query()->selectRaw('count(*)')
                ->whereColumn('answers.question_id', 'questions.id')
                ->where('answers.created_at', '>=', $since)],
            [$w['upvotes'], Vote::query()->selectRaw('count(*)')
                ->where('votes.votable_type', $question)->whereColumn('votes.votable_id', 'questions.id')
                ->where('votes.value', Vote::UP)->where('votes.created_at', '>=', $since)],
            [$w['upvotes'], Vote::query()->selectRaw('count(*)')
                ->join('answers', 'answers.id', '=', 'votes.votable_id')
                ->where('votes.votable_type', $answer)->whereColumn('answers.question_id', 'questions.id')
                ->where('votes.value', Vote::UP)->where('votes.created_at', '>=', $since)],
            [$w['comments'], Comment::query()->selectRaw('count(*)')
                ->where('comments.commentable_type', $question)->whereColumn('comments.commentable_id', 'questions.id')
                ->where('comments.created_at', '>=', $since)],
            [$w['comments'], Comment::query()->selectRaw('count(*)')
                ->join('answers', 'answers.id', '=', 'comments.commentable_id')
                ->where('comments.commentable_type', $answer)->whereColumn('answers.question_id', 'questions.id')
                ->where('comments.created_at', '>=', $since)],
        ];

        // One portable expression (MySQL and SQLite): the weighted sum of
        // correlated counts. Repeated in WHERE because an alias cannot be.
        $sql = [];
        $bindings = [];
        foreach ($parts as [$weight, $sub]) {
            $sql[] = '('.$sub->toSql().') * '.$weight;
            array_push($bindings, ...$sub->getBindings());
        }
        $score = implode(' + ', $sql);

        $query->selectRaw("({$score}) as trending_score", $bindings)
            ->whereRaw("({$score}) > 0", $bindings)
            ->orderByDesc('trending_score');
    }

    private function scoreSubquery(): Builder
    {
        return Vote::query()
            ->selectRaw('coalesce(sum(votes.value), 0)')
            ->where('votes.votable_type', (new Question)->getMorphClass())
            ->whereColumn('votes.votable_id', 'questions.id');
    }
}
