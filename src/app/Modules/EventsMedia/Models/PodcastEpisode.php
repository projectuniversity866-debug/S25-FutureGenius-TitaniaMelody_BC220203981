<?php

namespace App\Modules\EventsMedia\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One episode. Audio is either an uploaded file (private disk, streamed by
 * a controller so seeking works) or an https link to audio hosted elsewhere.
 * A transcript is required to publish: audio-only content needs a text
 * alternative (WCAG 2.1 success criterion 1.2.1).
 */
class PodcastEpisode extends Model
{
    /** Accepted uploads and their size limit. */
    public const AUDIO_MIMES = 'mp3,m4a,ogg,oga,wav,webm';

    public const MAX_AUDIO_KB = 51200;

    protected $fillable = [
        'number', 'title', 'slug', 'summary', 'show_notes', 'transcript', 'audio_path', 'audio_mime', 'audio_size', 'audio_url',
        'duration_seconds', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'duration_seconds' => 'integer', 'audio_size' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(PodcastSeries::class, 'podcast_series_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function hasAudio(): bool
    {
        return filled($this->audio_path) || filled($this->audio_url);
    }

    /** Where the player and the feed fetch the audio. */
    public function audioSource(): ?string
    {
        if ($this->audio_path) {
            return route('podcasts.audio', [$this->series, $this]);
        }

        return $this->audio_url;
    }

    /** Reasons this episode cannot be published yet; empty when it can. @return array<int, string> */
    public function missingForPublishing(): array
    {
        return array_values(array_filter([
            $this->hasAudio() ? null : 'audio (upload a file or link to it)',
            filled($this->transcript) ? null : 'a transcript',
        ]));
    }

    /** "42 min", "1 h 05 min". */
    public function durationLabel(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }
        $minutes = (int) round($this->duration_seconds / 60);

        return $minutes >= 60 ? intdiv($minutes, 60).' h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).' min' : max(1, $minutes).' min';
    }

    /** "00:42:05" for the feed. */
    public function durationClock(): ?string
    {
        return $this->duration_seconds ? gmdate('H:i:s', $this->duration_seconds) : null;
    }

    public static function makeSlug(PodcastSeries $series, string $title, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($title) ?: 'episode', 80, '');
        $slug = $base;
        $i = 1;
        // "edit" and "episodes" are the series' own pages (/podcasts/{series}/edit).
        while (in_array($slug, ['edit', 'episodes'], true) || $series->episodes()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
