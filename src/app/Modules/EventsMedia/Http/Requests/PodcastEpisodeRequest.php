<?php

namespace App\Modules\EventsMedia\Http\Requests;

use App\Modules\EventsMedia\Models\PodcastEpisode;
use Illuminate\Foundation\Http\FormRequest;

/** Adding or editing an episode: audio as an upload or an https link, show notes and the transcript. */
class PodcastEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('series'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'min:3', 'max:150'],
            'summary'          => ['required', 'string', 'min:10', 'max:255'],
            'show_notes'       => ['nullable', 'string', 'max:10000'],
            'transcript'       => ['nullable', 'string', 'max:200000'],
            'audio'            => ['nullable', 'file', 'mimes:'.PodcastEpisode::AUDIO_MIMES, 'max:'.PodcastEpisode::MAX_AUDIO_KB],
            'audio_url'        => ['nullable', 'url:https', 'max:500'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'remove_audio'     => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'audio.mimes'   => 'Upload MP3, M4A, OGG, WAV or WebM audio.',
            'audio.max'     => 'Audio files can be up to 50 MB. For longer episodes, host the file elsewhere and paste its link.',
            'audio.uploaded' => 'The file is larger than this server accepts. Host it elsewhere and paste its link instead.',
            'audio_url.url' => 'Paste the full https:// link to the audio file.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['audio_url' => 'audio link', 'duration_minutes' => 'length'];
    }

    /** Validated fields for the model (the file itself is handled separately). @return array<string, mixed> */
    public function episodeData(): array
    {
        $data = $this->safe()->except(['audio', 'duration_minutes', 'remove_audio']);
        $data['duration_seconds'] = $this->filled('duration_minutes') ? (int) $this->validated('duration_minutes') * 60 : null;

        return $data;
    }
}
