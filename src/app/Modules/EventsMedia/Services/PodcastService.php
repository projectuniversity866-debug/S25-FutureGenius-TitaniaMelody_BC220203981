<?php

namespace App\Modules\EventsMedia\Services;

use App\Modules\EventsMedia\Models\PodcastEpisode;
use App\Modules\EventsMedia\Models\PodcastSeries;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\ActivityLogger;
use App\Modules\Platform\Services\PrivateFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Podcast series and episodes (FR4 "Podcast publishing", roadmap 3.10). */
class PodcastService
{
    public function __construct(private PrivateFiles $files, private ActivityLogger $activity) {}

    /** @param  array{title: string, summary: string, description?: ?string}  $data */
    public function createSeries(User $host, array $data): PodcastSeries
    {
        return PodcastSeries::create($data + ['user_id' => $host->id, 'slug' => PodcastSeries::makeSlug($data['title'])]);
    }

    public function updateSeries(PodcastSeries $series, array $data): PodcastSeries
    {
        $series->update($data + ['slug' => PodcastSeries::makeSlug($data['title'], $series->id)]);

        return $series;
    }

    /** Removes every episode's uploaded audio too. */
    public function deleteSeries(PodcastSeries $series): void
    {
        $this->activity->record('podcast.deleted', $series, 'Deleted the podcast “'.$series->title.'”', ['episodes' => $series->episodes()->count()]);
        $series->episodes()->get()->each(fn (PodcastEpisode $e) => $this->files->delete($e->audio_path));
        $series->delete();
    }

    /**
     * @param  array<string, mixed>  $data  validated PodcastEpisodeRequest data
     */
    public function createEpisode(PodcastSeries $series, array $data, ?UploadedFile $audio): PodcastEpisode
    {
        return DB::transaction(function () use ($series, $data, $audio) {
            $episode = $series->episodes()->make($data + [
                'number' => (int) $series->episodes()->max('number') + 1,
                'slug'   => PodcastEpisode::makeSlug($series, $data['title']),
                'status' => 'draft',
            ]);
            $this->applyAudio($episode, $audio, false);
            $episode->save();

            return $episode;
        });
    }

    public function updateEpisode(PodcastEpisode $episode, array $data, ?UploadedFile $audio, bool $removeAudio): PodcastEpisode
    {
        $episode->fill($data + ['slug' => PodcastEpisode::makeSlug($episode->series, $data['title'], $episode->id)]);
        $this->applyAudio($episode, $audio, $removeAudio);
        if ($episode->isPublished() && $episode->missingForPublishing()) {
            throw ValidationException::withMessages(['audio' => 'A published episode needs '.implode(' and ', $episode->missingForPublishing()).'. Unpublish it first to remove them.']);
        }
        $episode->save();

        return $episode;
    }

    /** @throws ValidationException when audio or the transcript is missing */
    public function publish(PodcastEpisode $episode): PodcastEpisode
    {
        if ($missing = $episode->missingForPublishing()) {
            throw ValidationException::withMessages(['publish' => 'Before publishing, add '.implode(' and ', $missing).'.']);
        }
        $episode->update(['status' => 'published', 'published_at' => $episode->published_at ?? now()]);

        return $episode;
    }

    public function unpublish(PodcastEpisode $episode): PodcastEpisode
    {
        $episode->update(['status' => 'draft']);

        return $episode;
    }

    public function deleteEpisode(PodcastEpisode $episode): void
    {
        $this->files->delete($episode->audio_path);
        $episode->delete();
    }

    /** A new upload replaces the old file (and any link); a link replaces an old upload. */
    private function applyAudio(PodcastEpisode $episode, ?UploadedFile $audio, bool $remove): void
    {
        $old = $episode->getOriginal('audio_path');

        if ($audio) {
            $episode->fill([
                'audio_path' => $this->files->store($audio, 'podcasts/'.$episode->podcast_series_id),
                'audio_mime' => $audio->getMimeType(), 'audio_size' => $audio->getSize(), 'audio_url' => null,
            ]);
        } elseif ($remove || filled($episode->audio_url)) {
            $episode->fill(['audio_path' => null, 'audio_mime' => null, 'audio_size' => null]);
            if ($remove) {
                $episode->audio_url = null;
            }
        }

        if ($old && $old !== $episode->audio_path) {
            $this->files->delete($old);
        }
    }
}
