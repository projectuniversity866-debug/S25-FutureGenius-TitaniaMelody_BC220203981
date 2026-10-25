{{-- Horizontal bar chart for comparing a handful of categories.

     <x-bar-list :items="[['label' => 'EdTech', 'value' => 4, 'url' => …], …]" noun="question" />

     One hue (magnitude, not identity), bars capped thin with a rounded end,
     and every value written at the bar's tip — so it is readable as a plain
     list by screen readers and needs no tooltip. --}}
@props(['items', 'noun' => null, 'empty' => 'Nothing to show yet'])

@php $max = max(array_merge([1], array_column($items, 'value'))); @endphp

@if (array_sum(array_column($items, 'value')) === 0)
    <x-empty icon="bar-chart" :title="$empty" />
@else
    <ul {{ $attributes->class('aq-bars') }}>
        @foreach ($items as $item)
            <li>
                <div class="aq-bars-head">
                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="aq-bars-label">{{ $item['label'] }}</a>
                    @else
                        <span class="aq-bars-label">{{ $item['label'] }}</span>
                    @endif
                    <span class="aq-bars-value">
                        {{ number_format($item['value']) }}<span class="visually-hidden">
                        @if ($noun) {{ \Illuminate\Support\Str::plural($noun, $item['value']) }}@endif</span>
                    </span>
                </div>
                <div class="aq-bars-track" aria-hidden="true">
                    <span style="--w: {{ $item['value'] ? max(2, round($item['value'] / $max * 100, 1)) : 0 }}%"></span>
                </div>
            </li>
        @endforeach
    </ul>
@endif
