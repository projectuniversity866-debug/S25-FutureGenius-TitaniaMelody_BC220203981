@extends('layouts.app')
@section('title', 'Announcements')
@section('subtitle', 'Administration')

@section('content')
@php use App\Modules\Communications\Models\Announcement; @endphp
<x-page-header title="Announcements" subtitle="Banners shown at the top of every page between their start and end.">
    <x-slot:actions>
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> New announcement</a>
    </x-slot:actions>
</x-page-header>

@if ($announcements->isEmpty())
    <div class="card">
        <x-empty icon="megaphone" title="No announcements yet" :action="['url' => route('admin.announcements.create'), 'label' => 'Post one']" />
    </div>
@else
    <div class="card">
        <table class="table aq-table mb-0">
            <caption class="visually-hidden">Site announcements</caption>
            <thead><tr><th scope="col">Announcement</th><th scope="col">Shown</th><th scope="col">For</th><th scope="col">Closed by</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @foreach ($announcements as $a)
                    @php [$toneLabel, $toneIcon] = Announcement::TONES[$a->tone]; $phase = $a->phase(); @endphp
                    <tr>
                        <td data-label="Announcement"><i class="bi bi-{{ $toneIcon }} me-1" aria-hidden="true"></i><span class="visually-hidden">{{ $toneLabel }}: </span>{{ $a->title }}</td>
                        <td data-label="Shown" class="aq-text-sm">
                            <span class="badge {{ ['live' => 'text-bg-success', 'upcoming' => 'text-bg-primary', 'ended' => 'text-bg-secondary'][$phase] }}">
                                <i class="bi bi-{{ ['live' => 'broadcast', 'upcoming' => 'clock', 'ended' => 'archive'][$phase] }} me-1" aria-hidden="true"></i>{{ ['live' => 'Live', 'upcoming' => 'Upcoming', 'ended' => 'Ended'][$phase] }}
                            </span>
                            <span class="d-block aq-muted">{{ $a->timeLabel($a->starts_at) }}@if ($a->ends_at) – {{ $a->timeLabel($a->ends_at) }}@endif</span>
                        </td>
                        <td data-label="For" class="aq-text-sm">{{ Announcement::AUDIENCES[$a->audience] }}</td>
                        <td data-label="Closed by" class="aq-text-sm">{{ $a->is_dismissible ? $a->dismissals_count.' '.($a->dismissals_count === 1 ? 'member' : 'members') : 'Cannot be closed' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.announcements.edit', $a) }}" class="btn btn-quiet btn-sm">Edit<span class="visually-hidden"> “{{ $a->title }}”</span></a>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" class="d-inline"
                                  data-confirm-title="Delete “{{ $a->title }}”?" data-confirm="It disappears from every page and from Updates." data-confirm-button="Delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete<span class="visually-hidden"> “{{ $a->title }}”</span></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <nav aria-label="Announcement pages" class="mt-3">{{ $announcements->links() }}</nav>
@endif
@endsection
