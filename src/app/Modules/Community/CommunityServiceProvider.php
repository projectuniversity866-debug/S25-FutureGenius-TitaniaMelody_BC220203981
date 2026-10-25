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
