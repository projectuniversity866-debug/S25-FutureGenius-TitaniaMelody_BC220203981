<?php

namespace App\Modules\Identity\Support;

use App\Modules\Identity\Models\Role;

/**
 * Every permission AQDesk knows, with its default roles. Routes and policies
 * check these slugs (e.g. `can:users.manage`), never role names, so an
 * administrator can change what a role may do without touching code.
 *
 * New modules add their permissions here.
 */
final class PermissionCatalog
{
    /** Administrators can never lose this one — it is how roles are edited. */
    public const LOCKED = ['users.manage'];

    /** @return array<string, array{name: string, group: string, roles: array<int, string>}> */
    public static function all(): array
    {
        $everyone = [Role::ADMIN, Role::EXPERT, Role::MEMBER];

        return [
            'questions.post'        => ['name' => 'Ask and answer questions',          'group' => 'Community',      'roles' => $everyone],
            'questions.vote'        => ['name' => 'Vote on questions and answers',     'group' => 'Community',      'roles' => $everyone],
            'content.moderate'      => ['name' => 'Edit, close or delete anyone’s content', 'group' => 'Community', 'roles' => [Role::ADMIN]],
            'opportunities.apply'   => ['name' => 'Apply to jobs and internships',     'group' => 'Careers',        'roles' => $everyone],
            'opportunities.publish' => ['name' => 'Publish jobs and internships',      'group' => 'Careers',        'roles' => [Role::ADMIN, Role::EXPERT]],
            'forms.create'          => ['name' => 'Build forms and read their responses', 'group' => 'Platform', 'roles' => [Role::ADMIN, Role::EXPERT]],
            'events.create'         => ['name' => 'Organize events and see their attendees', 'group' => 'Events',  'roles' => [Role::ADMIN, Role::EXPERT]],
            'podcasts.publish'      => ['name' => 'Publish podcast series and episodes', 'group' => 'Events',    'roles' => [Role::ADMIN, Role::EXPERT]],
            'consulting.offer'      => ['name' => 'Offer consulting sessions',           'group' => 'Consulting', 'roles' => [Role::ADMIN, Role::EXPERT]],
            'newsletter.send'       => ['name' => 'Write and send newsletter campaigns', 'group' => 'Communications', 'roles' => [Role::ADMIN]],
            'announcements.manage'  => ['name' => 'Post site announcements and banners', 'group' => 'Communications', 'roles' => [Role::ADMIN]],
            'experts.verify'      => ['name' => 'Review expert verification requests', 'group' => 'Administration', 'roles' => [Role::ADMIN]],
            'users.manage'          => ['name' => 'Manage users, roles and permissions', 'group' => 'Administration', 'roles' => [Role::ADMIN]],
            'activity.view'         => ['name' => 'View the activity log',             'group' => 'Administration', 'roles' => [Role::ADMIN]],
        ];
    }
}
