<?php

namespace App\Modules\EventsMedia\Policies;

use App\Modules\EventsMedia\Models\PodcastEpisode;
use App\Modules\EventsMedia\Models\PodcastSeries;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

/** Who may publish podcasts and see drafts (FR4, roadmap 3.10). */
class PodcastPolicy
{
    /** A series is public once it has a published episode; until then only its host and moderators see it. */
    public function view(?User $user, PodcastSeries $series): Response
    {
        return $series->publishedEpisodes()->exists() || ($user && $this->update($user, $series))
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('podcasts.publish');
    }

    /** The host, or a moderator. Covers its episodes too. */
    public function update(User $user, PodcastSeries $series): bool
    {
        return ($user->id === $series->user_id && $user->hasPermission('podcasts.publish')) || $user->canModerate();
    }

    public function delete(User $user, PodcastSeries $series): bool
    {
        return $this->update($user, $series);
    }

    /** Draft episodes: 404 for everyone but the host and moderators. */
    public function listen(?User $user, PodcastSeries $series, PodcastEpisode $episode): Response
    {
        return $episode->isPublished() || ($user && $this->update($user, $series))
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
