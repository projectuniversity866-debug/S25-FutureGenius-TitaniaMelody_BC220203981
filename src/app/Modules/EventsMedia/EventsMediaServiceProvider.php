<?php

namespace App\Modules\EventsMedia;

use App\Modules\EventsMedia\Console\SendEventReminders;
use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Models\EventRegistration;
use App\Modules\EventsMedia\Models\PodcastEpisode;
use App\Modules\EventsMedia\Models\PodcastSeries;
use App\Modules\EventsMedia\Policies\EventPolicy;
use App\Modules\EventsMedia\Policies\PodcastPolicy;
use App\Modules\EventsMedia\Services\RegistrationService;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;

/**
 * FR4 Events and Podcasts — events with capacity and a waitlist (roadmap
 * 3.6), registration through the Form Builder (3.7), webinar links and
 * calendar files (3.8), scheduled reminders (3.9) and podcasts with
 * transcripts (3.10).
 */
class EventsMediaServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'EventsMedia';

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([SendEventReminders::class]);
        }
    }

    protected function policies(): array
    {
        return [Event::class => EventPolicy::class, PodcastSeries::class => PodcastPolicy::class];
    }

    protected function morphMap(): array
    {
        return ['event' => Event::class, 'podcast_series' => PodcastSeries::class, 'podcast_episode' => PodcastEpisode::class];
    }

    /** Events you registered for, and the ones you organize (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'event_registrations' => fn (User $user) => EventRegistration::with('event:id,title,starts_at')->where('user_id', $user->id)->get()
                ->map(fn (EventRegistration $r) => [
                    'event' => $r->event?->title, 'starts_at' => $r->event?->starts_at, 'status' => $r->status, 'registered_at' => $r->queued_at,
                ])->all(),
            'events_organized' => fn (User $user) => Event::where('user_id', $user->id)->get(['title', 'status', 'starts_at'])->toArray(),
            'podcasts_hosted'  => fn (User $user) => PodcastSeries::where('user_id', $user->id)->withCount('episodes')->get(['id', 'title', 'created_at'])
                ->map(fn (PodcastSeries $s) => ['series' => $s->title, 'episodes' => $s->episodes_count, 'created_at' => $s->created_at])->all(),
        ];
    }

    /**
     * Your seats are given up (the waitlist moves up), then your
     * registrations and their answers are removed. Events you organized stay,
     * shown under "Deleted user".
     */
    protected function personalDataErasers(): array
    {
        return [function (User $user) {
            $service = app(RegistrationService::class);
            EventRegistration::with('event', 'user')->where('user_id', $user->id)->where('status', '!=', 'cancelled')->get()
                ->each(fn (EventRegistration $r) => $service->cancel($r));
            EventRegistration::where('user_id', $user->id)->get()->each(function (EventRegistration $r) {
                $r->response()->delete();
                $r->delete();
            });
        }];
    }
}
