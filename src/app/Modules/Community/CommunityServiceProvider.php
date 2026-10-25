<?php

namespace App\Modules\Community;

use App\Modules\Community\Events\AnswerAccepted;
use App\Modules\Community\Events\AnswerPosted;
use App\Modules\Community\Listeners\SendAnswerNotifications;
use App\Modules\Community\Models\Answer;
use App\Modules\Community\Models\Question;
use App\Modules\Community\Models\Vote;
use App\Modules\Community\Policies\AnswerPolicy;
use App\Modules\Community\Policies\QuestionPolicy;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;

/**
 * FR2 Community Q&A — questions, answers, votes, tags, categories and
 * question moderation.
 */
class CommunityServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Community';

    protected function policies(): array
    {
        return [
            Question::class => QuestionPolicy::class,
            Answer::class   => AnswerPolicy::class,
        ];
    }

    protected function listeners(): array
    {
        return [
            AnswerPosted::class   => SendAnswerNotifications::class.'@handlePosted',
            AnswerAccepted::class => SendAnswerNotifications::class.'@handleAccepted',
        ];
    }

    protected function morphMap(): array
    {
        return [
            'question' => Question::class,
            'answer'   => Answer::class,
        ];
    }

    protected function relations(): array
    {
        return [
            User::class => [
                'questions' => fn (User $user) => $user->hasMany(Question::class),
                'answers'   => fn (User $user) => $user->hasMany(Answer::class),
                'votes'     => fn (User $user) => $user->hasMany(Vote::class),
            ],
        ];
    }
}
