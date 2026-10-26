<?php

use App\Modules\Careers\CareersServiceProvider;
use App\Modules\Communications\CommunicationsServiceProvider;
use App\Modules\Community\CommunityServiceProvider;
use App\Modules\Consulting\ConsultingServiceProvider;
use App\Modules\EventsMedia\EventsMediaServiceProvider;
use App\Modules\Governance\GovernanceServiceProvider;
use App\Modules\Identity\IdentityServiceProvider;
use App\Modules\Knowledge\KnowledgeServiceProvider;
use App\Modules\Platform\PlatformServiceProvider;
use App\Modules\Projects\ProjectsServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // AQDesk modules — each registers its own routes, views and migrations.
    PlatformServiceProvider::class,
    IdentityServiceProvider::class,
    CommunityServiceProvider::class,
    KnowledgeServiceProvider::class,
    CareersServiceProvider::class,
    ProjectsServiceProvider::class,
    EventsMediaServiceProvider::class,
    ConsultingServiceProvider::class,
    CommunicationsServiceProvider::class,
    GovernanceServiceProvider::class,
];
