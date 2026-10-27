<?php

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Models\Announcement;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Site announcements (FR7 "Community updates", roadmap 3.18). A member's
 * dismissal is stored with their account; a visitor's in their session.
 */
class AnnouncementService
{
    /** Banners on one page: at most this many, newest first. */
    public const MAX_BANNERS = 2;

    private const SESSION_KEY = 'dismissed_announcements';

    public function create(User $author, array $data): Announcement
    {
        $announcement = new Announcement($data);
        $announcement->created_by = $author->id;
        $announcement->save();

        return $announcement;
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        $announcement->update($data);

        return $announcement;
    }

    public function delete(Announcement $announcement): void
    {
        $announcement->delete();
    }

    /** @return Collection<int, Announcement> */
    public function bannersFor(?User $user, Session $session): Collection
    {
        return Announcement::live()->for($user)
            ->when($user, fn ($q) => $q->whereDoesntHave('dismissals', fn ($d) => $d->where('user_id', $user->id)))
            ->whereNotIn('id', $session->get(self::SESSION_KEY, []))
            ->latest('starts_at')->take(self::MAX_BANNERS)->get();
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
