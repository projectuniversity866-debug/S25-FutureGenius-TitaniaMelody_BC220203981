<?php

namespace App\Modules\Platform;

use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;
use App\Modules\Platform\Console\DocumentSchema;
use App\Modules\Platform\Models\ActivityLog;
use App\Modules\Platform\Models\Form;
use App\Modules\Platform\Models\FormResponse;
use App\Modules\Platform\Policies\FormPolicy;

/**
 * Shared services used by every module: in-app + email notifications, the
 * activity log (audit trail), private file storage, Markdown and the forms
 * engine (roadmap 2.4).
 */
class PlatformServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Platform';

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([DocumentSchema::class]);
        }
    }

    protected function policies(): array
    {
        return [Form::class => FormPolicy::class];
    }

    /** Your notifications, form responses and what you did as an administrator (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'notifications' => fn (User $user) => $user->notifications()->get()
                ->map(fn ($n) => $n->data + ['received_at' => $n->created_at, 'read_at' => $n->read_at])->all(),
            'form_responses' => fn (User $user) => FormResponse::with('form:id,title', 'answers.field:id,label')->where('user_id', $user->id)->get()
                ->map(fn (FormResponse $r) => [
                    'form' => $r->form?->title, 'submitted_at' => $r->created_at,
                    'answers' => $r->answers->mapWithKeys(fn ($a) => [$a->field?->label ?? 'Question' => $a->display()])->all(),
                ])->all(),
            'activity_log' => fn (User $user) => ActivityLog::where('user_id', $user->id)->get(['action', 'description', 'created_at'])->toArray(),
        ];
    }

    /** Notifications go; form responses stay but become anonymous. */
    protected function personalDataErasers(): array
    {
        return [function (User $user) {
            $user->notifications()->delete();
            FormResponse::where('user_id', $user->id)->update(['user_id' => null]);
        }];
    }

    protected function morphMap(): array
    {
        return [
            'form'          => Form::class,
            'form_response' => FormResponse::class,
        ];
    }
}
