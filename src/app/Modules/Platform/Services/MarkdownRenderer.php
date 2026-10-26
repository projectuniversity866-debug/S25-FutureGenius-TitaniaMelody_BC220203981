<?php

namespace App\Modules\Platform\Services;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * The one place user-written Markdown becomes HTML — used for display and
 * for the editor's preview, so both always look the same.
 *
 * Safety: raw HTML is escaped (shown as text, never run), unsafe link
 * schemes such as javascript: are removed, and links to other sites get
 * rel="nofollow noopener noreferrer" and open in a new tab.
 */
class MarkdownRenderer
{
    private ?MarkdownConverter $converter = null;

    public function render(?string $markdown): HtmlString
    {
        return new HtmlString((string) $this->converter()->convert((string) $markdown));
    }

    /** Plain text for excerpts and notifications. */
    public function plain(?string $markdown, int $limit = 0): string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $this->render($markdown)))));

        return $limit ? Str::limit($text, $limit) : $text;
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input'         => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 20,
            'external_link'      => [
                'internal_hosts'     => [parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost'],
                'open_in_new_window' => true,
                'nofollow'           => 'external',
                'noopener'           => 'external',
                'noreferrer'         => 'external',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new ExternalLinkExtension);

        return $this->converter = new MarkdownConverter($environment);
    }
}
