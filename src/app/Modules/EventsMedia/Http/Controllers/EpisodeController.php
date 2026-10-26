<?php

namespace App\Modules\EventsMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EventsMedia\Http\Requests\PodcastEpisodeRequest;
use App\Modules\EventsMedia\Models\PodcastEpisode;
use App\Modules\EventsMedia\Models\PodcastSeries;
use App\Modules\EventsMedia\Services\PodcastService;
use App\Modules\Platform\Services\PrivateFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** FR4 "Podcast publishing": episodes with audio and transcripts (roadmap 3.10). */
class EpisodeController extends Controller
{
    public function __construct(private PodcastService $podcasts) {}

    public function create(PodcastSeries $series): View
    {
        $this->authorize('update', $series);

        return view('events-media::podcasts.episode-form', ['series' => $series, 'episode' => null]);
    }

    public function store(PodcastEpisodeRequest $request, PodcastSeries $series): RedirectResponse
    {
        $episode = $this->podcasts->createEpisode($series, $request->episodeData(), $request->file('audio'));

        return redirect()->route('podcasts.episodes.show', [$series, $episode])
            ->with('status', 'Episode '.$episode->number.' saved as a draft. Publish it when the audio and transcript are in.');
    }

    public function show(PodcastSeries $series, PodcastEpisode $episode): View
    {
        $this->authorize('listen', [$series, $episode]);

        $episode->setRelation('series', $series->load('host'));
        $siblings = $series->publishedEpisodes()->whereIn('number', [$episode->number - 1, $episode->number + 1])->get()->keyBy('number');

        return view('events-media::podcasts.episode', [
            'series' => $series, 'episode' => $episode,
            'previous' => $siblings->get($episode->number - 1), 'next' => $siblings->get($episode->number + 1),
        ]);
    }

    public function edit(PodcastSeries $series, PodcastEpisode $episode): View
    {
        $this->authorize('update', $series);

        return view('events-media::podcasts.episode-form', compact('series', 'episode'));
    }

    public function update(PodcastEpisodeRequest $request, PodcastSeries $series, PodcastEpisode $episode): RedirectResponse
    {
        $this->podcasts->updateEpisode($episode->setRelation('series', $series), $request->episodeData(), $request->file('audio'), $request->boolean('remove_audio'));

        return redirect()->route('podcasts.episodes.show', [$series, $episode])->with('status', 'Episode saved.');
    }

    public function publish(PodcastSeries $series, PodcastEpisode $episode): RedirectResponse
    {
        $this->authorize('update', $series);

        $episode->isPublished() ? $this->podcasts->unpublish($episode) : $this->podcasts->publish($episode);

        return back()->with('status', $episode->isPublished() ? 'Episode published.' : 'Episode unpublished; it is a draft again.');
    }

    public function destroy(PodcastSeries $series, PodcastEpisode $episode): RedirectResponse
    {
        $this->authorize('update', $series);

        $this->podcasts->deleteEpisode($episode);

        return redirect()->route('podcasts.show', $series)->with('status', 'Episode deleted.');
    }

    /** The uploaded audio, streamed (seekable) after the same check as the episode page. */
    public function audio(PodcastSeries $series, PodcastEpisode $episode, PrivateFiles $files): BinaryFileResponse
    {
        $this->authorize('listen', [$series, $episode]);
        abort_unless($files->exists($episode->audio_path), 404);

        return $files->stream($episode->audio_path, $episode->audio_mime);
    }
}
