<?php

namespace App\Modules\Community\Services;

use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Models\ReputationEvent;
use App\Modules\Community\Models\Vote;
use App\Modules\Community\Notifications\BadgeEarned;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reputation, badges and the privileges reputation unlocks.
 *
 * Reputation is a ledger (reputation_events): every vote and accepted answer
 * writes a line, and removing the vote removes the line, so the total always
 * matches what the community did. A member's points are 1 + the ledger, and
 * never fall below 1.
 */
class ReputationService
{
    public const QUESTION_UPVOTE = 5;
    public const ANSWER_UPVOTE   = 10;
    public const DOWNVOTE        = -2;
    public const ACCEPTED_ANSWER = 15;

    /** Privileges and the reputation they need. Moderators have them all. */
    public const PRIVILEGES = [
        'comment.others' => ['points' => 5,  'label' => 'Comment on other people’s posts'],
        'vote.down'      => ['points' => 15, 'label' => 'Downvote questions and answers'],
    ];

    /** Badge catalogue: slug => [name, description, tier, icon]. */
    public const BADGES = [
        'first-question' => ['First question', 'Asked a first question', 'bronze', 'patch-question'],
        'curious-mind'   => ['Curious mind', 'Asked 5 questions', 'bronze', 'lightbulb'],
        'first-answer'   => ['First answer', 'Answered a first question', 'bronze', 'chat-left-text'],
        'helpful'        => ['Helpful', 'Answers received 5 upvotes in total', 'silver', 'hand-thumbs-up'],
        'problem-solver' => ['Problem solver', 'Wrote an accepted answer', 'silver', 'patch-check'],
        'trusted-expert' => ['Trusted expert', 'Wrote 5 accepted answers', 'gold', 'award'],
    ];

    // ------------------------------------------------------------- reading

    public function points(User $user): int
    {
        return max(1, 1 + (int) ReputationEvent::where('user_id', $user->id)->sum('points'));
    }

    /** @return array{points: int, breakdown: array<string, int>} */
    public function for(User $user): array
    {
        $sums = ReputationEvent::where('user_id', $user->id)
            ->selectRaw('type, sum(points) as total')->groupBy('type')->pluck('total', 'type');

        $breakdown = [];
        foreach (['question_upvoted', 'answer_upvoted', 'answer_accepted', 'downvoted', 'adjustment'] as $type) {
            if (isset($sums[$type]) || $type !== 'adjustment') {
                $breakdown[ReputationEvent::LABELS[$type]] = (int) ($sums[$type] ?? 0);
            }
        }

        return ['points' => $this->points($user), 'breakdown' => $breakdown];
    }

    /** @return Collection<int, ReputationEvent> */
    public function recent(User $user, int $limit = 10): Collection
    {
        return ReputationEvent::with('actor')->where('user_id', $user->id)
            ->latest('created_at')->latest('id')->take($limit)->get();
    }

    /** True when the user's reputation (or moderator role) unlocks the privilege. */
    public function has(User $user, string $privilege): bool
    {
        return $user->can('content.moderate') || $this->points($user) >= self::PRIVILEGES[$privilege]['points'];
    }

    /** @return array<int, array{label: string, points: int, unlocked: bool}> */
    public function privileges(User $user): array
    {
        return array_values(array_map(fn ($p, $key) => [
            'label' => $p['label'], 'points' => $p['points'], 'unlocked' => $this->has($user, $key),
        ], self::PRIVILEGES, array_keys(self::PRIVILEGES)));
    }

    /** @return array<int, array{name: string, description: string, tier: string, icon: string, earned: bool}> */
    public function badges(User $user): array
    {
        $earned = DB::table('user_badges')->where('user_id', $user->id)->pluck('badge')->all();

        return array_values(array_map(fn ($b, $slug) => [
            'name' => $b[0], 'description' => $b[1], 'tier' => $b[2], 'icon' => $b[3],
            'earned' => in_array($slug, $earned, true),
        ], self::BADGES, array_keys(self::BADGES)));
    }

    // ------------------------------------------------------------- writing

    /** Call after a vote is created or changed. */
    public function recordVote(Vote $vote): void
    {
        $this->forgetVote($vote);

        if ($owner = $this->writeVoteEvent($vote)) {
            $this->awardBadges($owner);
        }
    }

    /** Call before a vote is deleted. */
    public function forgetVote(Vote $vote): void
    {
        ReputationEvent::where('subject_type', $vote->getMorphClass())->where('subject_id', $vote->id)->delete();
    }

    /** Call before a post is deleted: its votes and acceptance stop counting. */
    public function forgetPost(Question|Answer $post): void
    {
        ReputationEvent::where('subject_type', (new Vote)->getMorphClass())
            ->whereIn('subject_id', $post->votes()->select('id'))->delete();

        ReputationEvent::where('subject_type', $post->getMorphClass())->where('subject_id', $post->id)->delete();
    }

    /** Call after an answer is accepted (the previously accepted one loses its points). */
    public function recordAcceptance(Answer $answer): void
    {
        ReputationEvent::where('type', 'answer_accepted')->where('subject_type', $answer->getMorphClass())
            ->whereIn('subject_id', $answer->question->answers()->select('id'))->delete();

        if ($answer->user_id !== $answer->question->user_id) {
            ReputationEvent::create([
                'user_id' => $answer->user_id, 'actor_id' => $answer->question->user_id, 'type' => 'answer_accepted',
                'points' => self::ACCEPTED_ANSWER, 'subject_type' => $answer->getMorphClass(), 'subject_id' => $answer->id,
            ]);
        }

        $this->awardBadges($answer->user);
    }

    /**
     * Award any badge the user now qualifies for. Badges are kept once
     * earned, even if a vote is later removed.
     */
    public function awardBadges(User $user, bool $notify = true): void
    {
        $answers = $user->answers();
        $checks = [
            'first-question' => fn () => $user->questions()->exists(),
            'curious-mind'   => fn () => $user->questions()->count() >= 5,
            'first-answer'   => fn () => $answers->exists(),
            'helpful'        => fn () => ReputationEvent::where('user_id', $user->id)->where('type', 'answer_upvoted')->count() >= 5,
            'problem-solver' => fn () => $user->answers()->where('is_accepted', true)->exists(),
            'trusted-expert' => fn () => $user->answers()->where('is_accepted', true)->count() >= 5,
        ];

        $have = DB::table('user_badges')->where('user_id', $user->id)->pluck('badge')->all();

        foreach ($checks as $slug => $qualifies) {
            if (! in_array($slug, $have, true) && $qualifies()) {
                DB::table('user_badges')->insert(['user_id' => $user->id, 'badge' => $slug, 'awarded_at' => now()]);
                if ($notify) {
                    $user->notify(new BadgeEarned(self::BADGES[$slug], $user->id));
                }
            }
        }
    }

    /** Recreate the whole ledger from votes and accepted answers. */
    public function rebuild(): void
    {
        DB::transaction(function () {
            ReputationEvent::query()->delete();

            Vote::with('votable.user')->orderBy('id')->each(fn (Vote $vote) => $this->writeVoteEvent($vote));

            Answer::with('question')->where('is_accepted', true)->each(function (Answer $answer) {
                if ($answer->user_id !== $answer->question->user_id) {
                    ReputationEvent::create([
                        'user_id' => $answer->user_id, 'actor_id' => $answer->question->user_id, 'type' => 'answer_accepted',
                        'points' => self::ACCEPTED_ANSWER, 'subject_type' => $answer->getMorphClass(), 'subject_id' => $answer->id,
                    ]);
                }
            });

            User::query()->each(fn (User $user) => $this->awardBadges($user, notify: false));
        });
    }

    /** Write the ledger line for a vote; returns whoever gained or lost points. */
    private function writeVoteEvent(Vote $vote): ?User
    {
        $owner = $vote->votable?->user;
        if (! $owner || $owner->id === $vote->user_id) {
            return null;
        }

        ReputationEvent::create([
            'user_id' => $owner->id, 'actor_id' => $vote->user_id,
            'type' => $vote->value > 0 ? ($vote->votable instanceof Answer ? 'answer_upvoted' : 'question_upvoted') : 'downvoted',
            'points' => $vote->value > 0 ? ($vote->votable instanceof Answer ? self::ANSWER_UPVOTE : self::QUESTION_UPVOTE) : self::DOWNVOTE,
            'subject_type' => $vote->getMorphClass(), 'subject_id' => $vote->id,
        ]);

        return $owner;
    }
}
