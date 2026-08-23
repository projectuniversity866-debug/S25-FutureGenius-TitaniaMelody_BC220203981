@extends('layouts.app')
@section('title', 'My applications')

@section('content')
<h1 class="h4 fw-bold mb-3">My applications</h1>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">Opportunities you have applied to</caption>
            <thead class="table-light">
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
                        <th scope="row" class="fw-semibold">
                            @if ($application->jobPost)
                                <a href="{{ route('jobs.show', $application->jobPost) }}"
                                   class="text-decoration-none">{{ $application->jobPost->title }}</a>
                            @else
                                <span class="text-muted">Opportunity removed</span>
                            @endif
                        </th>
                        <td>{{ $application->jobPost?->company ?? '—' }}</td>
                        <td class="small text-muted">{{ $application->created_at->format('d M Y') }}</td>
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
                                'message' => 'Browse the Jobs board to find an opportunity.',
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
