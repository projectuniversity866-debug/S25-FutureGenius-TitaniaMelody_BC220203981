{{-- Line chart over days, drawn by the Vue component LineChart.

     <x-line-chart :labels="$activity['labels']" :series="$activity['series']"
                   label="Community activity over the last 14 days" />

     The data table below the chart is rendered on the server: it is the
     no-JavaScript fallback and the accessible "view as table" for everyone. --}}
@props(['labels', 'series', 'label'])

@php
    $dates = array_map(fn ($d) => \Illuminate\Support\Carbon::parse($d), $labels);
    $hasData = array_sum(array_merge(...array_map(fn ($s) => $s['values'], $series))) > 0;
@endphp

<figure {{ $attributes->class('aq-chart mb-0') }}>
    @if ($hasData)
        <x-vue name="LineChart" class="aq-chart-canvas"
               :props="['labels' => $labels, 'series' => $series, 'label' => $label]" />
    @else
        <x-empty icon="graph-up" title="No activity in this period yet" />
    @endif

    <details class="aq-chart-table">
        <summary>View as table</summary>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <caption class="visually-hidden">{{ $label }}</caption>
                <thead>
                    <tr>
                        <th scope="col">Day</th>
                        @foreach ($series as $s)
                            <th scope="col" class="text-end">{{ $s['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dates as $i => $date)
                        <tr>
                            <th scope="row" class="fw-normal">
                                <time datetime="{{ $date->toDateString() }}">{{ $date->format('D j M') }}</time>
                            </th>
                            @foreach ($series as $s)
                                <td class="text-end aq-num">{{ $s['values'][$i] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
