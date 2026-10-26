<?php

namespace App\Modules\Community\Notifications;

use App\Modules\Platform\Notifications\AppNotification;

/** In-app only (category "general" has no email switch). */
class BadgeEarned extends AppNotification
{
    /** @param  array{0: string, 1: string, 2: string, 3: string}  $badge */
    public function __construct(private array $badge, private int $earnerId) {}

    protected function title(): string
    {
        return 'You earned the “'.$this->badge[0].'” badge';
    }

    protected function body(): string
    {
        return $this->badge[1].'. It now shows on your profile.';
    }

    protected function url(): string
    {
        // The earner — not whoever's vote triggered the badge.
        return route('profile.show', $this->earnerId);
    }

    protected function icon(): string
    {
        return 'award';
    }
}
