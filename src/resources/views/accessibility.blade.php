@extends('layouts.app')
@section('title', 'Accessibility')
@section('subtitle', 'Statement and display options')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Accessibility"
               subtitle="AQDesk aims to be usable by everyone, including people who use a keyboard, a screen reader, magnification or other assistive technology." />

<x-card title="Display options" icon="universal-access" class="mb-4">
    <p class="aq-text-sm aq-muted">These are also in the <i class="bi bi-universal-access" aria-hidden="true"></i> menu at the top of every page. They are remembered in this browser, for visitors and members alike.</p>
    <form method="POST" action="{{ route('accessibility.update') }}">
        @csrf
        <fieldset class="mb-3">
            <legend class="form-label">Text size</legend>
            @foreach (\App\Support\AccessibilityPreferences::TEXT_SIZES as $value => $label)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="text" id="text_{{ $value }}" value="{{ $value }}" @checked($prefs->textSize === $value)>
                    <label class="form-check-label" for="text_{{ $value }}">{{ $label }}</label>
                </div>
            @endforeach
        </fieldset>
        @foreach (\App\Support\AccessibilityPreferences::SWITCHES as $key => [$label, $hint])
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" role="switch" id="page_{{ $key }}" name="{{ $key }}" value="1"
                       aria-describedby="page_{{ $key }}_hint" @checked($prefs->has($key))>
                <label class="form-check-label" for="page_{{ $key }}">{{ $label }}</label>
                <div id="page_{{ $key }}_hint" class="form-text mt-0">{{ $hint }}</div>
            </div>
        @endforeach
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-accent btn-sm">Save display options</button>
            <button type="submit" name="reset" value="1" class="btn btn-quiet btn-sm">Reset</button>
        </div>
    </form>
</x-card>

<div class="card">
    <div class="card-body p-4 aq-legal">
        <h2>Our standard</h2>
        <p>We aim to meet the <strong>Web Content Accessibility Guidelines (WCAG) 2.1 at level AA</strong>.</p>

        <h2>What we have done</h2>
        <ul>
            <li>Every page works with a keyboard alone, shows where the focus is, and starts with a “Skip to content” link.</li>
            <li>Pages use proper headings, landmarks and labelled form fields; errors are linked to the field they belong to.</li>
            <li>Colour is never the only signal: statuses also have words or icons, and colours meet contrast requirements in light and dark themes.</li>
            <li>Things that change on the page — votes, saved items, new notifications, toasts — are announced to screen readers.</li>
            <li>Charts have a “View as table” alternative; tables keep their structure on phones.</li>
            <li>Every page works without JavaScript.</li>
            <li>The display options above: larger text, high contrast, a readable font, more line spacing and reduced motion. Your system’s “reduce motion” setting is respected too.</li>
        </ul>

        <h2>Known limitations</h2>
        <ul>
            <li>Content written by members (questions, articles) may lack headings or image descriptions; moderators fix this when reported.</li>
            <li>Linked learning resources and external websites are outside our control.</li>
        </ul>

        <h2>Tell us about a problem</h2>
        <p class="mb-0">
            If something is hard to use, please tell an administrator — for example by reporting the page’s content with the
            <i class="bi bi-flag" aria-hidden="true"></i> Report link, or by asking in the Q&amp;A under the tag <em>accessibility</em>.
            This statement was last reviewed in October 2026.
        </p>
    </div>
</div>
@endsection
