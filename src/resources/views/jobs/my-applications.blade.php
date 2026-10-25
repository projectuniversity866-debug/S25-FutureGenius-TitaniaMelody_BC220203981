@extends('layouts.app')
@section('title', 'My applications')
@section('subtitle', 'Every opportunity you applied to')

@section('content')
<div class="aq-page-head">
    <div>
        <h1>My applications</h1>
        <p>Status is updated by the person who posted the opportunity.</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <caption class="visually-hidden">Opportunities you have applied to</caption>
            <thead>
                <tr>
                    <th scope="col">Opportunity</th>
                    <th scope="col">Company</th>
                    <th scope="col">Applied</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr>
                        <th scope="row" class="fw-normal">
                            @if ($application->jobPost)
                                <a href="{{ route('jobs.show', $application->jobPost) }}"
                                   class="fw-semibold text-decoration-none">{{ $application->jobPost->title }}</a>
                            @else
                                <span style="color: var(--aq-muted);">Opportunity removed</span>
                            @endif
                        </th>
                        <td>{{ $application->jobPost?->company ?? '—' }}</td>
                        <td class="aq-num" style="color: var(--aq-muted);">{{ $application->created_at->format('d M Y') }}</td>
                        <td>
                            <span class="badge text-bg-{{ $application->statusColor() }}">
                                {{ ucfirst($application->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('applications.destroy', $application) }}"
                                  onsubmit="return confirm('Withdraw this application?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Withdraw</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            @include('partials.empty', [
                                'icon' => 'send',
                                'title' => 'You have not applied to anything yet',
                                'message' => 'Browse the jobs board to find an opportunity.',
                                'action' => ['url' => route('jobs.index'), 'label' => 'Browse opportunities'],
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Application list pages" class="mt-4">
    {{ $applications->links() }}
</nav>
@endsection
