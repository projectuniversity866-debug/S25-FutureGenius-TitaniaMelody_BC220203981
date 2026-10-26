{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- RSS 2.0 podcast feed (roadmap 3.10). Blade's {{ }} escapes text for XML. --}}
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
    <title>{{ $series->title }}</title>
    <link>{{ route('podcasts.show', $series) }}</link>
    <atom:link href="{{ route('podcasts.feed', $series) }}" rel="self" type="application/rss+xml" />
    <description>{{ $series->description ?: $series->summary }}</description>
    <language>en</language>
    <itunes:author>{{ $series->host?->name ?? 'AQDesk' }}</itunes:author>
    <itunes:summary>{{ $series->summary }}</itunes:summary>
    <itunes:explicit>false</itunes:explicit>
    @foreach ($episodes as $episode)
        <item>
            <title>{{ $episode->title }}</title>
            <link>{{ route('podcasts.episodes.show', [$series, $episode]) }}</link>
            <guid isPermaLink="false">aqdesk-podcast-episode-{{ $episode->id }}</guid>
            <pubDate>{{ $episode->published_at->toRfc2822String() }}</pubDate>
            <description>{{ $episode->summary }}</description>
            <itunes:episode>{{ $episode->number }}</itunes:episode>
            @if ($episode->durationClock())<itunes:duration>{{ $episode->durationClock() }}</itunes:duration>@endif
            @if ($episode->audioSource())
                <enclosure url="{{ $episode->audioSource() }}" length="{{ $episode->audio_size ?? 0 }}" type="{{ $episode->audio_mime ?? 'audio/mpeg' }}" />
            @endif
        </item>
    @endforeach
</channel>
</rss>
