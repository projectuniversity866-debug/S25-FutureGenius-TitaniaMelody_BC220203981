{{-- Filter form for a list page, with the active filters shown as
     removable chips and the number of results.

     <x-filter-panel :action="route('admin.users.index')" label="Filter users"
                     :filters="['q' => 'Search', 'role' => ['Role', $roleNames]]"
                     :total="$users->total()" noun="user">
         …labelled inputs named q and role…
     </x-filter-panel>

     `filters` maps each query parameter to its chip label, or to
     [label, [value => display name]] when the raw value is a slug.
     `keep` lists parameters carried over unchanged (e.g. the active tab).
     `stacked` lays the fields out vertically, for a sidebar. --}}
@props([
    'action',
    'label' => 'Filters',
    'filters' => [],
    'keep' => [],
    'total' => null,
    'noun' => 'result',
    'stacked' => false,
])

@php
    $active = [];
    foreach ($filters as $param => $config) {
        $value = request()->query($param);
        if (blank($value) || ! is_string($value)) {
            continue;
        }
        [$chipLabel, $names] = is_array($config) ? $config : [$config, []];
        $active[] = [
            'label'  => $chipLabel,
            'value'  => $names[$value] ?? $value,
            'remove' => $action.'?'.http_build_query(request()->except([$param, 'page'])),
        ];
    }
    $clearAll = $action.(($kept = request()->only($keep)) ? '?'.http_build_query($kept) : '');
@endphp

<form method="GET" action="{{ $action }}" role="search" aria-label="{{ $label }}"
      {{ $attributes->class(['aq-filter', 'is-stacked' => $stacked]) }}>
    @foreach ($keep as $param)
        @if (is_string(request($param)) && filled(request($param)))
            <input type="hidden" name="{{ $param }}" value="{{ request($param) }}">
        @endif
    @endforeach

    <div class="aq-filter-fields">{{ $slot }}</div>

    <div class="aq-filter-actions">
        <button type="submit" class="btn btn-accent btn-sm">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i> Apply
        </button>
    </div>
</form>

@if ($active || $total !== null)
    <div class="aq-filter-summary">
        @if ($total !== null)
            <p class="mb-0" role="status">
                <strong class="aq-num">{{ number_format($total) }}</strong>
                {{ \Illuminate\Support\Str::plural($noun, $total) }}{{ $active ? ($total === 1 ? ' matches' : ' match') : '' }}
            </p>
        @endif

        @foreach ($active as $chip)
            <span class="aq-chip-filter">
                <span>{{ $chip['label'] }}: <strong>{{ $chip['value'] }}</strong></span>
                <a href="{{ $chip['remove'] }}" aria-label="Remove filter {{ $chip['label'] }}: {{ $chip['value'] }}">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            </span>
        @endforeach

        @if ($active)
            <a href="{{ $clearAll }}" class="aq-filter-clear">Clear all</a>
        @endif
    </div>
@endif
