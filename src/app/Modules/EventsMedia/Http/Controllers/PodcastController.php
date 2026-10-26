<?php

namespace App\Modules\EventsMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EventsMedia\Http\Requests\PodcastSeriesRequest;
use App\Modules\EventsMedia\Models\PodcastSeries;
use App\Modules\EventsMedia\Services\PodcastService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** FR4 "Podcast publishing": series (roadmap 3.10). */
class PodcastController extends Controller
{
    public function __construct(private PodcastService $podcasts) {}

    /** Series with at least one published episode; hosts also see their own unpublished ones. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';

        $series = PodcastSeries::query()
            ->where(fn ($w) => $w->whereHas('episodes', fn ($e) => $e->where('status', 'published'))
                ->when($user, fn ($w) => $w->orWhere('user_id', $user->id)))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('title', 'like', "%{$q}%")->orWhere('summary', 'like', "%{$q}%")
                ->orWhereHas('episodes', fn ($e) => $e->where('status', 'published')
                    ->where(fn ($t) => $t->where('title', 'like', "%{$q}%")->orWhere('transcript', 'like', "%{$q}%")))))
            ->with('host')
            ->withCount(['episodes as published_count' => fn ($e) => $e->where('status', 'published')])
            ->withMax(['episodes as latest_at' => fn ($e) => $e->where('status', 'published')], 'published_at')
            ->orderByDesc('latest_at')->paginate(12)->withQueryString();

        return view('events-media::podcasts.index', compact('series', 'q'));
    }

    public function create(): View
    {
        $this->authorize('create', PodcastSeries::class);

        return view('events-media::podcasts.series-form', ['series' => null]);
    }

    public function store(PodcastSeriesRequest $request): RedirectResponse
    {
        $series = $this->podcasts->createSeries($request->user(), $request->validated());

        return redirect()->route('podcasts.show', $series)->with('status', 'Series created. Add its first episode.');
    }

    public function show(Request $request, PodcastSeries $series): View
    {
        $this->authorize('view', $series);

        $manage = (bool) $request->user()?->can('update', $series);
        $episodes = ($manage ? $series->episodes() : $series->publishedEpisodes())->paginate(20);

        return view('events-media::podcasts.series', ['series' => $series->load('host'), 'episodes' => $episodes, 'manage' => $manage]);
    }

    public function edit(PodcastSeries $series): View
    {
        $this->authorize('update', $series);

        return view('events-media::podcasts.series-form', compact('series'));
    }

    public function update(PodcastSeriesRequest $request, PodcastSeries $series): RedirectResponse
    {
        $this->podcasts->updateSeries($series, $request->validated());

        return redirect()->route('podcasts.show', $series)->with('status', 'Series saved.');
    }

    public function destroy(PodcastSeries $series): RedirectResponse
    {
        $this->authorize('delete', $series);

        $this->podcasts->deleteSeries($series);

        return redirect()->route('podcasts.index')->with('status', 'Series and its episodes deleted.');
    }

    /** RSS 2.0 feed with audio enclosures, for podcast apps. Published episodes only. */
    public function feed(PodcastSeries $series): Response
    {
        $this->authorize('view', $series);

        $episodes = $series->publishedEpisodes()->limit(100)->get()->each->setRelation('series', $series);

        return response()->view('events-media::podcasts.feed', ['series' => $series->load('host'), 'episodes' => $episodes])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
