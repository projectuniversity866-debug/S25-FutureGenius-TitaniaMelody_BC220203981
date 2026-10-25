<?php

namespace App\Modules\Platform;

use App\Modules\ModuleServiceProvider;

/**
 * Shared services used by every module: in-app + email notifications, the
 * activity log (audit trail) and private file storage.
 */
class PlatformServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Platform';
}
