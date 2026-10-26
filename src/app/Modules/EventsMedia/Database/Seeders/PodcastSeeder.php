<?php

namespace App\Modules\EventsMedia\Database\Seeders;

use App\Modules\EventsMedia\Models\PodcastEpisode;
use App\Modules\EventsMedia\Models\PodcastSeries;
use App\Modules\Identity\Models\User;
use App\Modules\Platform\Services\PrivateFiles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * A demo podcast (roadmap 3.10): two published episodes with transcripts and
 * a draft. The demo audio is a few seconds of generated silence, so the
 * player works without shipping real recordings. Adds only what is missing.
 */
class PodcastSeeder extends Seeder
{
    public function run(): void
    {
        $expert = User::where('email', 'expert@aqdesk.test')->value('id');
        $series = PodcastSeries::firstOrCreate(['slug' => 'final-year-voices'], [
            'user_id' => $expert, 'title' => 'Final-Year Voices',
            'summary' => 'Students and mentors talk about final-year projects: ideas, setbacks and what they learned.',
            'description' => "A short conversation every two weeks.\n\nEvery episode has a **full transcript**.",
        ]);

        $episodes = [
            [1, 'Choosing a project you will still like in six months', 'How to pick a final-year project that is useful, doable and yours.', 30, true,
                "Ahmed: Welcome to Final-Year Voices. Today Sara joins us to talk about choosing a project.\nSara: Thanks. The best advice I got was to pick a problem I had seen myself.\nAhmed: Not the most impressive idea, then?\nSara: No. The one you can explain to a friend in one sentence."],
            [2, 'Testing early: the habit that saved my demo', 'Why writing tests from week one made the final demo calm instead of scary.', 25, true,
                "Ahmed: This time we talk about testing.\nSara: I started with one test for the sign-in page. By the end there were two hundred.\nAhmed: Did it slow you down?\nSara: At first. Then it was the only reason I could change things the night before the demo."],
            [3, 'Accessibility from the first screen', 'Draft: planning an episode on building accessibility in from the start.', null, false, null],
        ];

        foreach ($episodes as [$number, $title, $summary, $minutes, $published, $transcript]) {
            if ($series->episodes()->where('number', $number)->exists()) {
                continue;
            }
            $series->episodes()->create([
                'number' => $number, 'title' => $title, 'slug' => PodcastEpisode::makeSlug($series, $title), 'summary' => $summary,
                'transcript' => $transcript, 'duration_seconds' => $minutes ? $minutes * 60 : null,
                'show_notes' => $published ? "- Mentioned: the AQDesk Learning Hub\n- Questions? Ask in the Q&A" : null,
                'status' => $published ? 'published' : 'draft', 'published_at' => $published ? now()->subDays(30 - $number * 10) : null,
            ] + ($published ? $this->silence($series->id, $number) : []));
        }
    }

    /** Three seconds of silent 8 kHz mono WAV on the private disk. @return array<string, mixed> */
    private function silence(int $seriesId, int $number): array
    {
        $samples = str_repeat(chr(128), 8000 * 3);
        $wav = 'RIFF'.pack('V', 36 + strlen($samples)).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8)
            .'data'.pack('V', strlen($samples)).$samples;
        $path = "podcasts/{$seriesId}/demo-episode-{$number}.wav";
        Storage::disk(PrivateFiles::DISK)->put($path, $wav);

        return ['audio_path' => $path, 'audio_mime' => 'audio/wav', 'audio_size' => strlen($wav)];
    }
}
