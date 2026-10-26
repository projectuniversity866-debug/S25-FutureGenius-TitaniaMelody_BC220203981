<?php

namespace App\Modules\Community;

use App\Modules\Community\Events\AnswerAccepted;
use App\Modules\Community\Events\AnswerPosted;
use App\Modules\Community\Events\QuestionAsked;
use App\Modules\Community\Listeners\NotifyTagFollowers;
use App\Modules\Community\Listeners\SendAnswerNotifications;
use App\Modules\Community\Console\RebuildReputation;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Bookmark;
use App\Modules\Community\Models\Comment;
use App\Modules\Community\Policies\CommentPolicy;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Models\ReputationEvent;
use Illuminate\Support\Facades\DB;
use App\Modules\Community\Models\Tag;
use App\Modules\Community\Models\Vote;
use App\Modules\Community\Policies\AnswerPolicy;
use App\Modules\Community\Policies\QuestionPolicy;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;

/**
 * FR2 Community Q&A — questions, answers, votes, comments, tags, categories,
 * reputation, bookmarks, tag follows and question moderation.
 */
class CommunityServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Community';

    protected function policies(): array
    {
        return [
            Question::class => QuestionPolicy::class,
            Answer::class   => AnswerPolicy::class,
            Comment::class  => CommentPolicy::class,
        ];
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([RebuildReputation::class]);
        }
    }

    protected function listeners(): array
    {
        return [
            AnswerPosted::class   => SendAnswerNotifications::class.'@handlePosted',
            AnswerAccepted::class => SendAnswerNotifications::class.'@handleAccepted',
            QuestionAsked::class  => NotifyTagFollowers::class.'@handle',
        ];
    }

    protected function morphMap(): array
    {
        return [
            'question' => Question::class,
            'answer'   => Answer::class,
            'comment'  => Comment::class,
            'vote'     => Vote::class,
        ];
    }

    /** What you wrote and did in Q&A (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'questions' => fn (User $user) => Question::where('user_id', $user->id)->get(['title', 'body', 'created_at'])->toArray(),
            'answers'   => fn (User $user) => Answer::with('question:id,title')->where('user_id', $user->id)->get()
                ->map(fn (Answer $a) => ['question' => $a->question?->title, 'body' => $a->body, 'created_at' => $a->created_at])->all(),
            'comments'  => fn (User $user) => Comment::where('user_id', $user->id)->get(['body', 'created_at'])->toArray(),
            'votes'     => fn (User $user) => Vote::where('user_id', $user->id)->get(['votable_type', 'votable_id', 'value', 'created_at'])->toArray(),
            'saved_questions' => fn (User $user) => $user->bookmarks()->get(['bookmarkable_type', 'bookmarkable_id', 'created_at'])->toArray(),
            'followed_tags'   => fn (User $user) => $user->followedTags()->pluck('name')->all(),
            'reputation'      => fn (User $user) => [
                'events' => ReputationEvent::where('user_id', $user->id)->get(['type', 'points', 'created_at'])->toArray(),
                'badges' => DB::table('user_badges')->where('user_id', $user->id)->get(['badge', 'awarded_at'])->toArray(),
            ],
        ];
    }

    /** Private lists go; public posts and votes stay, as "Deleted user". */
    protected function personalDataErasers(): array
    {
        return [function (User $user) {
            $user->bookmarks()->delete();
            $user->followedTags()->detach();
        }];
    }

    protected function relations(): array
    {
        return [
            User::class => [
                'questions'    => fn (User $user) => $user->hasMany(Question::class),
                'answers'      => fn (User $user) => $user->hasMany(Answer::class),
                'votes'        => fn (User $user) => $user->hasMany(Vote::class),
                'bookmarks'    => fn (User $user) => $user->hasMany(Bookmark::class),
                'followedTags' => fn (User $user) => $user->belongsToMany(
                    Tag::class, 'tag_follows', 'user_id', 'tag_id', 'id', 'id', 'followedTags'
                )->withTimestamps(),
            ],
        ];
    }
}
