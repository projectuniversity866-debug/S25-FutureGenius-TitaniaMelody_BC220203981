{{-- Accessibility toolbar (roadmap 2.7). A plain form that saves the
     preferences in a cookie; resources/js/a11y.js applies each change at once
     when JavaScript is available. --}}
@php
    $prefs = \App\Support\AccessibilityPreferences::fromRequest(request());
    $sizes = \App\Support\AccessibilityPreferences::TEXT_SIZES;
@endphp

<div class="dropdown">
    <button type="button" class="aq-icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
            aria-label="Accessibility options">
        <i class="bi bi-universal-access" aria-hidden="true"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end aq-a11y-menu">
        <form method="POST" action="{{ route('accessibility.update') }}" data-aq-a11y>
            @csrf
            <p class="aq-strong mb-2">Display options</p>
            <fieldset class="mb-3">
                <legend class="aq-label mb-1">Text size</legend>
                <div class="aq-a11y-sizes">
                    @foreach ($sizes as $value => $label)
                        <input type="radio" class="btn-check" name="text" id="a11y_text_{{ $value }}" value="{{ $value }}" @checked($prefs->textSize === $value)>
                        <label class="btn btn-quiet btn-sm" for="a11y_text_{{ $value }}">{{ $label }}</label>
                    @endforeach
                </div>
            </fieldset>
            @foreach (\App\Support\AccessibilityPreferences::SWITCHES as $key => [$label, $hint])
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="a11y_{{ $key }}" name="{{ $key }}" value="1"
                           aria-describedby="a11y_{{ $key }}_hint" @checked($prefs->has($key))>
                    <label class="form-check-label" for="a11y_{{ $key }}">{{ $label }}</label>
                    <div id="a11y_{{ $key }}_hint" class="form-text mt-0">{{ $hint }}</div>
                </div>
            @endforeach
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <button type="submit" class="btn btn-accent btn-sm" data-aq-a11y-apply>Apply</button>
                <button type="submit" name="reset" value="1" class="btn btn-quiet btn-sm">Reset</button>
                <a href="{{ route('accessibility') }}" class="aq-text-sm ms-auto">Accessibility statement</a>
            </div>
        </form>
    </div>
</div>
