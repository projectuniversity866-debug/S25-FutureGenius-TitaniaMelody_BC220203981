{{-- A single figure with its label, for dashboards and summaries.

     <x-stat label="Questions" :value="42" icon="patch-question" tone="blue"
             :delta="3" :trend="[0,1,0,2,…]" hint="of 12 posted" />

     delta  — change over the last 7 days, shown as "▲ 3 this week"
     trend  — one value per day; drawn as a sparkline (decorative: the value
              and delta carry the information in text)
     hint   — a short qualifier shown instead of, or after, the delta --}}
@props(['label', 'value', 'icon' => null, 'tone' => 'accent', 'delta' => null, 'trend' => null, 'hint' => null])

@php
    $compact = function ($n) {
        if (! is_numeric($n)) return $n;
        return $n >= 10000 ? round($n / 1000, 1).'K' : number_format($n);
    };
    $spark = null;
    if ($trend && count($trend) > 1) {
        $w = 112; $h = 28; $pad = 4;
        $max = max(max($trend), 1);
        $stepX = ($w - 2 * $pad) / (count($trend) - 1);
        $pts = [];
        foreach (array_values($trend) as $i => $v) {
            $pts[] = [round($pad + $i * $stepX, 1), round($h - $pad - ($v / $max) * ($h - 2 * $pad), 1)];
        }
        $spark = ['w' => $w, 'h' => $h, 'points' => implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $pts)), 'last' => end($pts)];
    }
@endphp

<div {{ $attributes->class('aq-metric') }}>
    <div class="aq-metric-top">
        @if ($icon)
            <span class="aq-metric-icon {{ $tone === 'accent' ? '' : "is-$tone" }}" aria-hidden="true">
                <i class="bi bi-{{ $icon }}"></i>
            </span>
        @endif
        @if ($spark)
            <svg class="aq-spark" width="{{ $spark['w'] }}" height="{{ $spark['h'] }}" viewBox="0 0 {{ $spark['w'] }} {{ $spark['h'] }}" aria-hidden="true" focusable="false">
                <polyline points="{{ $spark['points'] }}" />
                <circle cx="{{ $spark['last'][0] }}" cy="{{ $spark['last'][1] }}" r="3.5" />
            </svg>
        @endif
    </div>
    <div class="aq-metric-value">{{ $compact($value) }}</div>
    <p class="aq-metric-label">{{ $label }}</p>
    @if ($delta !== null || $hint)
        <p class="aq-metric-delta {{ $delta > 0 ? 'is-up' : '' }}">
            @if ($delta !== null)
                @if ($delta > 0)
                    <i class="bi bi-arrow-up-short" aria-hidden="true"></i>
                    <span><strong>{{ number_format($delta) }}</strong> this week</span>
                @else
                    <span>No change this week</span>
                @endif
            @endif
            @if ($hint)
                <span>{{ $hint }}</span>
            @endif
        </p>
    @endif
</div>
