<?php

namespace Tests\Feature\AqDesk;

use App\Modules\Careers\Models\JobPost;
use App\Modules\Community\Models\Question;
use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\EventsMedia\Models\Event;
use App\Modules\EventsMedia\Models\PodcastEpisode;
use App\Modules\Identity\Models\User;
use App\Modules\Knowledge\Models\Article;
use App\Modules\Knowledge\Models\LearningPath;
use App\Modules\Platform\Models\Form;
use App\Modules\Projects\Models\Project;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Route;

/**
 * Suite WA — automated WCAG 2.1 AA checks on every page (4.3). Each page is
 * opened as a visitor, a member and an administrator, and its HTML is
 * checked for the things a machine can check reliably. Cases WA1–WA3.
 */
class AccessibilityAuditTest extends AqDeskTestCase
{
    /** Pages with no parameters that are not pages (files, JSON, redirects). */
    private const NOT_PAGES = ['notifications/summary', 'privacy/my-data/download', 'modules/newsletter', 'email/verify'];

    /** @var array<string, array<int, string>> page => problems */
    private array $problems = [];

    private int $pages = 0;

    /** @return array<int, string> every GET page without parameters */
    private function plainPages(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{') && ! str_starts_with($r->uri(), '_') && $r->uri() !== 'up'
                && ! in_array($r->uri(), self::NOT_PAGES, true) && ! str_starts_with($r->uri(), 'auth/') && ! str_starts_with($r->uri(), 'storage'))
            ->map(fn ($r) => '/'.ltrim($r->uri(), '/'))->unique()->values()->all();
    }

    /** @return array<int, array{0: string, 1: ?User}> detail pages built from the demo data, with who opens them */
    private function detailPages(): array
    {
        $q = Question::firstOrFail();
        $job = JobPost::firstOrFail();
        $event = Event::where('status', 'published')->firstOrFail();
        $episode = PodcastEpisode::with('series')->where('status', 'published')->firstOrFail();
        $consultant = ConsultantProfile::firstOrFail();
        $article = Article::published()->firstOrFail();
        $path = LearningPath::whereNotNull('published_at')->firstOrFail();
        $project = Project::firstOrFail();
        $form = Form::firstOrFail();

        return [
            [route('questions.show', $q), null], [route('questions.history', $q), null], [route('questions.edit', $q), $q->user],
            [route('jobs.show', $job), null], [route('jobs.apply', $job), $this->otherMember()], [route('jobs.applicants', $job), $job->user], [route('jobs.edit', $job), $job->user],
            [route('events.show', $event), null], [route('events.attendees', $event), $event->organizer], [route('events.edit', $event), $event->organizer],
            [route('podcasts.show', $episode->series), null], [route('podcasts.episodes.show', [$episode->series, $episode]), null],
            [route('consultants.show', $consultant), null], [route('consultants.book', $consultant), $this->otherMember()],
            [route('articles.show', $article), null], [route('articles.edit', $article), $article->author],
            [route('learning.paths.show', $path), null], [route('learning.paths.edit', $path), $this->admin()],
            [route('projects.show', $project), $project->owner], [route('projects.threads.index', $project), $project->owner],
            [route('projects.tasks.index', $project), $project->owner], [route('projects.edit', $project), $project->owner],
            [route('forms.edit', $form), $form->creator], [route('forms.responses', $form), $form->creator], [route('forms.fill', $form), $this->member()],
            [route('profile.show', $this->member()), null], [route('reports.create', ['question', $q->id]), $this->otherMember()],
        ];
    }

    private function audit(string $url, ?User $as): void
    {
        $as ? $this->actingAs($as) : $this->signOut();
        $response = $this->get($url);
        if ($response->status() !== 200 || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return;
        }
        $this->pages++;

        $dom = new DOMDocument;
        @$dom->loadHTML($response->getContent());
        $x = new DOMXPath($dom);
        $who = $as ? $as->email : 'visitor';
        $found = [];

        // 3.1.1 language of the page; 2.4.1 bypass blocks; 1.3.1 landmarks; 2.4.6 one main heading.
        if ($x->query('//html[@lang]')->length !== 1) $found[] = 'no lang on <html>';
        if ($x->query('//a[contains(@class,"skip-link")]')->length !== 1) $found[] = 'no skip link';
        if ($x->query('//main[@id="main-content"]')->length !== 1) $found[] = 'no main landmark';
        if (($h1 = $x->query('//h1')->length) !== 1) $found[] = "$h1 <h1> elements";
        if ($x->query('//title[normalize-space()]')->length !== 1) $found[] = 'no page title';

        // 1.3.1 heading levels do not skip (h2 → h4).
        $last = 1;
        foreach ($x->query('//main//h1|//main//h2|//main//h3|//main//h4|//main//h5|//main//h6') as $h) {
            $level = (int) substr($h->nodeName, 1);
            if ($level > $last + 1) $found[] = "heading skips from h$last to h$level: “".trim($h->textContent).'”';
            $last = $level;
        }

        // 4.1.1 unique ids; 1.3.1 aria-describedby / label[for] point to real ids.
        $ids = [];
        foreach ($x->query('//*[@id]') as $el) {
            $id = $el->getAttribute('id');
            if (isset($ids[$id])) $found[] = "duplicate id “{$id}”";
            $ids[$id] = true;
        }
        foreach ($x->query('//*[@aria-describedby]|//*[@aria-labelledby]') as $el) {
            foreach (preg_split('/\s+/', trim($el->getAttribute('aria-describedby').' '.$el->getAttribute('aria-labelledby'))) as $ref) {
                if ($ref !== '' && ! isset($ids[$ref])) $found[] = "aria reference to missing id “{$ref}”";
            }
        }

        // 1.3.1 / 4.1.2 every form control has a label.
        foreach ($x->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="reset")]|//select|//textarea') as $el) {
            if (! $this->labelled($x, $el)) $found[] = 'unlabelled '.$el->nodeName.' name="'.$el->getAttribute('name').'"';
        }

        // 1.1.1 images have alt text; 2.4.4 / 4.1.2 links and buttons have a name.
        foreach ($x->query('//img[not(@alt)]') as $img) $found[] = 'image without alt: '.$img->getAttribute('src');
        foreach ($x->query('//a[@href]|//button') as $el) {
            if (! $this->named($el)) $found[] = 'unnamed '.$el->nodeName.' '.($el->getAttribute('href') ?: $el->getAttribute('class'));
        }

        // Project rule (and 1.4.4 / 1.4.12): no inline styles, so user styles and the toolbar can override everything.
        if ($x->query('//body//*[@style]')->length) $found[] = $x->query('//body//*[@style]')->length.' inline style attribute(s)';

        if ($found) {
            $this->problems["$url ($who)"] = array_values(array_unique($found));
        }
    }

    private function labelled(DOMXPath $x, DOMElement $el): bool
    {
        $id = $el->getAttribute('id');

        return $el->getAttribute('aria-label') !== '' || $el->getAttribute('aria-labelledby') !== '' || $el->getAttribute('title') !== ''
            || ($id !== '' && $x->query('//label[@for="'.$id.'"]')->length > 0)
            || $x->query('ancestor::label', $el)->length > 0;
    }

    private function named(DOMElement $el): bool
    {
        if (trim($el->textContent) !== '' || $el->getAttribute('aria-label') !== '' || $el->getAttribute('title') !== '' || $el->getAttribute('aria-labelledby') !== '') {
            return true;
        }
        foreach ($el->getElementsByTagName('img') as $img) {
            if (trim($img->getAttribute('alt')) !== '') return true;
        }

        return false;
    }

    private function report(): string
    {
        return collect($this->problems)->map(fn ($p, $page) => $page.":\n  - ".implode("\n  - ", $p))->implode("\n");
    }

    /** @return array<string, string> the colour tokens of one theme block in aqdesk.css */
    private function tokens(string $theme): array
    {
        $css = file_get_contents(public_path('css/aqdesk.css'));
        preg_match('/\[data-bs-theme="'.$theme.'"\]\s*\{(.*?)\n\}/s', $css, $block);
        preg_match_all('/--(aq-[a-z0-9-]+):\s*(#[0-9a-f]{6})\s*;/i', $block[1] ?? '', $m);

        return array_combine($m[1], $m[2]);
    }

    /** WCAG relative-luminance contrast ratio of two #rrggbb colours. */
    private function contrast(string $a, string $b): float
    {
        $lum = function (string $hex) {
            $c = array_map(fn ($v) => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, sscanf($hex, '#%02x%02x%02x'));

            return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
        };
        [$l1, $l2] = [max($lum($a), $lum($b)), min($lum($a), $lum($b))];

        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    /** WA4 — 1.4.3 / 1.4.11: every text colour on every background it is used on, in both themes. */
    public function test_wa4_colour_contrast(): void
    {
        $failures = [];
        foreach (['light', 'dark'] as $theme) {
            $t = $this->tokens($theme);
            $this->assertGreaterThan(20, count($t), "no tokens found for $theme");
            $pairs = [];
            foreach (['aq-ink', 'aq-ink-2', 'aq-muted', 'aq-accent', 'aq-accent-ink'] as $text) {
                foreach (['aq-bg', 'aq-bg-tint', 'aq-surface', 'aq-surface-2', 'aq-surface-3'] as $bg) {
                    $pairs[] = [$text, $bg, 4.5];
                }
            }
            foreach (['blue', 'amber', 'green', 'red', 'accent'] as $tone) {
                $pairs[] = ["aq-$tone-ink", "aq-$tone-soft", 4.5];   // badges, notices, chips
            }
            $pairs[] = ['aq-on-accent', 'aq-accent', 4.5];        // primary buttons
            $pairs[] = ['aq-on-accent', 'aq-accent-hover', 4.5];
            $pairs[] = ['aq-accent', 'aq-surface', 3.0];          // focus outline and selected controls (non-text)
            $pairs[] = ['aq-chart-1', 'aq-surface', 3.0];         // chart bars and lines

            foreach ($pairs as [$fg, $bg, $min]) {
                $ratio = $this->contrast($t[$fg], $t[$bg]);
                if ($ratio < $min) {
                    $failures[] = sprintf('%s: %s on %s is %.2f:1 (needs %.1f:1)', $theme, $fg, $bg, $ratio, $min);
                }
            }
        }
        $this->assertSame([], $failures, implode("\n", $failures));
    }

    /** WA1 — every public page, as a visitor. */
    public function test_wa1_public_pages(): void
    {
        foreach ($this->plainPages() as $url) {
            $this->audit($url, null);
        }
        $this->assertGreaterThanOrEqual(15, $this->pages);
        $this->assertSame([], $this->problems, "Accessibility problems on {$this->pages} pages:\n".$this->report());
    }

    /** WA2 — every page with no parameters, as a member and as an administrator. */
    public function test_wa2_signed_in_pages(): void
    {
        foreach ([$this->member(), $this->admin()] as $user) {
            foreach ($this->plainPages() as $url) {
                $this->audit($url, $user);
            }
        }
        $this->assertGreaterThanOrEqual(80, $this->pages);
        $this->assertSame([], $this->problems, "Accessibility problems on {$this->pages} pages:\n".$this->report());
    }

    /** WA3 — detail and edit pages of every module, opened by the person who would use them. */
    public function test_wa3_detail_pages(): void
    {
        foreach ($this->detailPages() as [$url, $as]) {
            $this->audit($url, $as);
        }
        $this->assertGreaterThanOrEqual(25, $this->pages);
        $this->assertSame([], $this->problems, "Accessibility problems on {$this->pages} pages:\n".$this->report());
    }
}
