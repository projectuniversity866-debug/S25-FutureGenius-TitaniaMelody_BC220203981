<?php

namespace App\Modules\Governance\Support;

/**
 * The current terms of use and privacy policy (roadmap 2.6). Raising VERSION
 * asks every member to accept again (banner on every page until they do).
 */
final class LegalDocuments
{
    public const VERSION = '2026-10';

    public const UPDATED = '28 October 2026';

    /** How long each kind of personal data is kept (shown on the privacy page). */
    public const RETENTION = [
        'Account and profile'      => 'Until you delete your account',
        'Questions, answers, comments, articles' => 'Kept after account deletion, shown as “Deleted user”',
        'CVs and applications'     => 'Until you withdraw the application or delete your account',
        'Notifications'            => 'Until you delete your account',
        'Activity (audit) log'     => '365 days',
        'Moderation decisions'     => 'Kept to show how the community is governed',
    ];
}
