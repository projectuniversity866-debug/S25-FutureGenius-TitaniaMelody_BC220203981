{{-- User-written Markdown, rendered safely (see MarkdownRenderer).
     <x-markdown :text="$question->body" /> --}}
@props(['text'])

<div {{ $attributes->class(['prose', 'aq-markdown']) }}>{{ app(\App\Modules\Platform\Services\MarkdownRenderer::class)->render($text) }}</div>
