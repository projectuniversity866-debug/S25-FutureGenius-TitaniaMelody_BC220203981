@extends('layouts.app')
@section('title', 'Activity log')
@section('subtitle', 'Administration')

@section('content')
<x-page-header title="Activity log"
               subtitle="Every administrative and moderation action, with who did it and when. Kept for {{ \App\Modules\Platform\Models\ActivityLog::RETENTION_DAYS }} days." />

<x-filter-panel :action="route('admin.activity.index')" label="Filter activity"
                :filters="['action' => 'Action']" :total="$entries->total()" noun="entry">
    <div>
        <label for="action" class="form-label">Action</label>
        <select id="action" name="action" class="form-select form-select-sm">
            <option value="">All actions</option>
            @foreach ($actions as $a)
                <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
            @endforeach
        </select>
    </div>
</x-filter-panel>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle aq-table">
            <caption class="visually-hidden">Activity log</caption>
            <thead>
                <tr>
                    <th scope="col">What happened</th>
                    <th scope="col">By</th>
                    <th scope="col">Action</th>
                    <th scope="col">When</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <th scope="row" class="fw-normal">{{ $entry->description }}</th>
                        <td data-label="By"><span>{{ $entry->user?->name ?? 'System' }}</span></td>
                        <td data-label="Action"><code class="aq-text-xs">{{ $entry->action }}</code></td>
                        <td data-label="When">
                            <time class="aq-text-xs aq-muted" datetime="{{ $entry->created_at->toIso8601String() }}"
                                  title="{{ $entry->created_at->format('d M Y, H:i') }}">{{ $entry->created_at->diffForHumans() }}</time>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty icon="clock-history" title="No activity recorded yet" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Activity log pages" class="mt-4">{{ $entries->links() }}</nav>
@endsection
