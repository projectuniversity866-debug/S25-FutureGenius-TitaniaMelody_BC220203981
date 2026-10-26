<?php

namespace Tests\Feature\AqDesk;

use App\Modules\EventsMedia\Models\PodcastEpisode;
use App\Modules\EventsMedia\Models\PodcastSeries;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Suite PC — podcast series, episodes and transcripts (3.10). Cases PC1–PC10.
 */
class PodcastTest extends AqDeskTestCase
{
    private const TRANSCRIPT = "Ahmed: Welcome to the show.\nSara: Thanks for having me & my notes.";

    private function series($host = null, string $title = 'Testing Talk'): PodcastSeries
    {
        $host ??= $this->expert();
        $this->actingAs($host)->post(route('podcasts.store'), ['title' => $title, 'summary' => 'Conversations about testing web apps.'])
            ->assertSessionHasNoErrors();

        return PodcastSeries::where('title', $title)->latest('id')->firstOrFail();
    }

    private function episode(PodcastSeries $series, array $data = []): PodcastEpisode
    {
        $this->actingAs($series->host)->post(route('podcasts.episodes.store', $series), $data + [
            'title' => 'Why we test', 'summary' => 'The first episode: why tests matter.',
        ])->assertSessionHasNoErrors();

        return $series->episodes()->firstOrFail();
    }

    private function publishedEpisode(PodcastSeries $series, array $data = []): PodcastEpisode
    {
        $episode = $this->episode($series, $data + [
            'audio' => UploadedFile::fake()->create('episode.mp3', 200, 'audio/mpeg'), 'transcript' => self::TRANSCRIPT, 'duration_minutes' => 42,
        ]);
        $this->actingAs($series->host)->put(route('podcasts.episodes.publish', [$series, $episode]))->assertSessionHasNoErrors();

        return $episode->fresh();
    }

    /** PC1 — a series without published episodes is the host's alone; members cannot start one. */
    public function test_pc1_series_visibility_and_permission(): void
    {
        $series = $this->series();

        $this->actingAs($this->expert())->get(route('podcasts.show', $series))->assertOk()->assertSee('No episodes yet');
        $this->actingAs($this->expert())->get(route('podcasts.index'))->assertSee('Testing Talk')->assertSee('Not public yet');
        $this->actingAs($this->member())->get(route('podcasts.show', $series))->assertNotFound();
        $this->actingAs($this->member())->get(route('podcasts.index'))->assertDontSee('Testing Talk');
        $this->actingAs($this->member())->get(route('podcasts.create'))->assertForbidden();
        $this->actingAs($this->member())->post(route('podcasts.store'), ['title' => 'Mine', 'summary' => 'A member tries to start a series.'])->assertForbidden();
    }

    /** PC2 — publishing needs audio and a transcript (WCAG 1.2.1); then the series and episode are public. */
    public function test_pc2_publish_requires_transcript(): void
    {
        Storage::fake('local');
        $series = $this->series();
        $episode = $this->episode($series, ['audio' => UploadedFile::fake()->create('ep.mp3', 100, 'audio/mpeg')]);

        $this->assertSame(['draft', 1], [$episode->status, $episode->number]);
        $this->actingAs($series->host)->get(route('podcasts.episodes.show', [$series, $episode]))->assertSee('To publish, add a transcript');
        $this->actingAs($series->host)->put(route('podcasts.episodes.publish', [$series, $episode]))->assertSessionHasErrors('publish');
        $this->signOut();
        $this->get(route('podcasts.episodes.show', [$series, $episode]))->assertNotFound();

        $this->actingAs($series->host)->put(route('podcasts.episodes.update', [$series, $episode]), [
            'title' => 'Why we test', 'summary' => 'The first episode: why tests matter.', 'transcript' => self::TRANSCRIPT,
        ])->assertSessionHasNoErrors();
        $this->actingAs($series->host)->put(route('podcasts.episodes.publish', [$series, $episode]));
        $this->assertNotNull($episode->fresh()->published_at);

        $this->signOut();
        $this->get(route('podcasts.index'))->assertSee('Testing Talk');
        $this->get(route('podcasts.episodes.show', [$series, $episode]))->assertOk()
            ->assertSee('<audio controls', false)->assertSee('<strong>Sara:</strong> Thanks for having me &amp; my notes.', false);
    }

    /** PC3 — uploaded audio is private storage, streamed with seeking (Range); drafts' audio is hidden. */
    public function test_pc3_audio_stream(): void
    {
        Storage::fake('local');
        $series = $this->series();
        $episode = $this->publishedEpisode($series);
        Storage::disk('local')->assertExists($episode->audio_path);
        $this->assertStringStartsWith('podcasts/'.$series->id.'/', $episode->audio_path);
        Storage::disk('local')->put($episode->audio_path, str_repeat('x', 1000));   // fake uploads are empty

        $this->signOut();
        $this->get(route('podcasts.audio', [$series, $episode]))->assertOk();
        $this->get(route('podcasts.audio', [$series, $episode]), ['Range' => 'bytes=0-99'])->assertStatus(206);

        $this->episode($series, ['title' => 'Draft episode', 'audio' => UploadedFile::fake()->create('d.mp3', 10, 'audio/mpeg')]);
        $draft = $series->episodes()->where('title', 'Draft episode')->firstOrFail();
        $this->signOut();
        $this->get(route('podcasts.audio', [$series, $draft]))->assertNotFound();
    }

    /** PC4 — audio by link (https only); an upload replaces a link and a link replaces (and deletes) an upload. */
    public function test_pc4_audio_link(): void
    {
        Storage::fake('local');
        $series = $this->series();
        $this->actingAs($series->host)->post(route('podcasts.episodes.store', $series), ['title' => 'Linked', 'summary' => 'Audio hosted somewhere else.', 'audio_url' => 'http://x.org/a.mp3'])
            ->assertSessionHasErrors('audio_url');
        $this->actingAs($series->host)->post(route('podcasts.episodes.store', $series), ['title' => 'Linked', 'summary' => 'Audio hosted somewhere else.', 'audio' => UploadedFile::fake()->create('a.exe', 10)])
            ->assertSessionHasErrors('audio');

        $episode = $this->episode($series, ['audio' => UploadedFile::fake()->create('ep.mp3', 50, 'audio/mpeg')]);
        $old = $episode->audio_path;
        $this->actingAs($series->host)->put(route('podcasts.episodes.update', [$series, $episode]), [
            'title' => 'Why we test', 'summary' => 'The first episode: why tests matter.', 'audio_url' => 'https://archive.org/download/demo/ep.mp3',
        ])->assertSessionHasNoErrors();

        $episode->refresh();
        $this->assertSame(['https://archive.org/download/demo/ep.mp3', null], [$episode->audio_url, $episode->audio_path]);
        Storage::disk('local')->assertMissing($old);
        $this->assertSame('https://archive.org/download/demo/ep.mp3', $episode->audioSource());
    }

    /** PC5 — a published episode keeps its audio and transcript; unpublishing makes it a draft again. */
    public function test_pc5_published_rules(): void
    {
        Storage::fake('local');
        $series = $this->series();
        $episode = $this->publishedEpisode($series);

        $this->actingAs($series->host)->put(route('podcasts.episodes.update', [$series, $episode]), [
            'title' => 'Why we test', 'summary' => 'The first episode: why tests matter.', 'transcript' => '', 'remove_audio' => 1,
        ])->assertSessionHasErrors('audio');
        $this->assertNotNull($episode->fresh()->transcript);

        $this->actingAs($series->host)->put(route('podcasts.episodes.publish', [$series, $episode]));
        $this->assertSame('draft', $episode->fresh()->status);
        $this->actingAs($this->otherExpert())->put(route('podcasts.episodes.publish', [$series, $episode]))->assertForbidden();
    }

    /** PC6 — the RSS feed lists published episodes with their audio, escaped; drafts are left out. */
    public function test_pc6_feed(): void
    {
        Storage::fake('local');
        $series = $this->series(null, 'Tests & Talks');
        $episode = $this->publishedEpisode($series);
        $this->episode($series, ['title' => 'Secret draft']);

        $this->signOut();
        $response = $this->get(route('podcasts.feed', $series))->assertOk();
        $this->assertStringStartsWith('application/rss+xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('Tests & Talks', (string) $xml->channel->title);
        $this->assertCount(1, $xml->channel->item);
        $this->assertSame(route('podcasts.audio', [$series, $episode]), (string) $xml->channel->item->enclosure['url']);
        $this->assertSame('00:42:00', (string) $xml->channel->item->children('itunes', true)->duration);
        $this->get(route('podcasts.show', $series))->assertSee('application/rss+xml', false);
    }

    /** PC7 — search covers titles and transcripts of published episodes. */
    public function test_pc7_search(): void
    {
        Storage::fake('local');
        $this->publishedEpisode($this->series());

        $this->get(route('podcasts.index', ['q' => 'having me']))->assertSee('Testing Talk');
        $this->get(route('podcasts.index', ['q' => 'quantum']))->assertDontSee('Testing Talk')->assertSee('No podcasts match');
        $this->get(route('podcasts.index', ['q' => ['x']]))->assertOk();
    }

    /** PC8 — episodes are numbered; slugs are per series and avoid "edit"; the wrong series is 404. */
    public function test_pc8_numbers_and_slugs(): void
    {
        Storage::fake('local');
        $a = $this->series();
        $b = $this->series(null, 'Another Show');
        $first = $this->publishedEpisode($a);
        $this->actingAs($a->host)->post(route('podcasts.episodes.store', $a), ['title' => 'Edit', 'summary' => 'An episode literally called Edit.']);
        $edit = $a->episodes()->where('title', 'Edit')->firstOrFail();
        $sameSlug = $this->publishedEpisode($b);

        $this->assertSame(2, $edit->number);
        $this->assertSame('edit-2', $edit->slug);
        $this->assertSame($first->slug, $sameSlug->slug);
        $this->get(route('podcasts.episodes.show', [$b, $sameSlug]))->assertOk();
        $this->get('/podcasts/'.$b->slug.'/not-an-episode')->assertNotFound();
        $this->actingAs($a->host)->get(route('podcasts.edit', $a))->assertOk()->assertSee('Edit series');
    }

    /** PC9 — deleting a series removes its episodes and audio; it is logged. */
    public function test_pc9_delete(): void
    {
        Storage::fake('local');
        $series = $this->series();
        $episode = $this->publishedEpisode($series);

        $this->actingAs($this->member())->delete(route('podcasts.destroy', $series))->assertForbidden();
        $this->actingAs($series->host)->delete(route('podcasts.destroy', $series))->assertRedirect(route('podcasts.index'));
        $this->assertDatabaseMissing('podcast_episodes', ['id' => $episode->id]);
        Storage::disk('local')->assertMissing($episode->audio_path);
        $this->assertDatabaseHas('activity_logs', ['action' => 'podcast.deleted']);
    }

    /** PC10 — the demo podcast is public with two episodes and a hidden draft; the host's export lists it. */
    public function test_pc10_seeded_podcast_and_export(): void
    {
        $series = PodcastSeries::where('slug', 'final-year-voices')->firstOrFail();

        $this->get(route('podcasts.show', $series))->assertOk()->assertSee('Testing early')->assertDontSee('Accessibility from the first screen');
        $episode = $series->publishedEpisodes()->firstOrFail();
        $this->get(route('podcasts.audio', [$series, $episode]))->assertOk();
        $this->get(route('podcasts.episodes.show', [$series, $episode]))->assertSee('<strong>Sara:</strong>', false);

        $data = $this->actingAs($this->expert())->get(route('privacy.export'))->json('data');
        $this->assertSame('Final-Year Voices', $data['podcasts_hosted'][0]['series']);
    }
}
