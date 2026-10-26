<?php

namespace App\Modules\Platform;

use App\Modules\ModuleServiceProvider;
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

    protected function policies(): array
    {
        return [Form::class => FormPolicy::class];
    }

    protected function morphMap(): array
    {
        return [
            'form'          => Form::class,
            'form_response' => FormResponse::class,
        ];
    }
}
