<?php

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Models\Announcement;
use App\Modules\Communications\Models\AnnouncementDismissal;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Site announcements (FR7 "Community updates", roadmap 3.18). A member's
 * dismissal is stored with their account; a visitor's in their session.
 */
class AnnouncementService
{
    /** Banners on one page: at most this many, newest first. */
    public const MAX_BANNERS = 2;

    private const SESSION_KEY = 'dismissed_announcements';

    private const CACHE_PREFIX = 'announcements.live.';

    private const CACHE_SECONDS = 60;

    public function create(User $author, array $data): Announcement
    {
        $announcement = new Announcement($data);
        $announcement->created_by = $author->id;
        $announcement->save();
        $this->forgetCache();

        return $announcement;
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        $announcement->update($data);
        $this->forgetCache();

        return $announcement;
    }

    public function delete(Announcement $announcement): void
    {
        $announcement->delete();
        $this->forgetCache();
    }

    /**
     * Every page asks for this, so the live list per audience is cached for a
     * minute (cleared on every change); only the member's own dismissals are
     * read each time (roadmap 4.2).
     *
     * @return Collection<int, Announcement>
     */
    public function bannersFor(?User $user, Session $session): Collection
    {
        $live = Cache::remember(self::CACHE_PREFIX.($user ? 'members' : 'guests'), self::CACHE_SECONDS,
            fn () => Announcement::live()->for($user)->latest('starts_at')->get());
        if ($live->isEmpty()) {
            return $live;
        }

        $dismissed = $user
            ? AnnouncementDismissal::where('user_id', $user->id)->whereIn('announcement_id', $live->modelKeys())->pluck('announcement_id')->all()
            : $session->get(self::SESSION_KEY, []);

        return $live->reject(fn (Announcement $a) => in_array($a->id, $dismissed))->take(self::MAX_BANNERS)->values();
    }

    private function forgetCache(): void
    {
        Cache::forget(self::CACHE_PREFIX.'members');
        Cache::forget(self::CACHE_PREFIX.'guests');
    }

    public function dismiss(Announcement $announcement, ?User $user, Session $session): void
    {
        if (! $announcement->is_dismissible) {
            return;
        }

        $user
            ? $announcement->dismissals()->firstOrCreate(['user_id' => $user->id])
            : $session->push(self::SESSION_KEY, $announcement->id);
    }
}
