@extends('layouts.app')
@section('title', 'Moderate questions')

@section('content')
<h1 class="h4 fw-bold mb-1">Moderate questions</h1>
<p class="text-muted small mb-4">Close a question to stop new answers, or delete it entirely.</p>

<form method="GET" action="{{ route('admin.questions.index') }}" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-10">
                <label for="q" class="form-label small">Search</label>
                <input type="search" id="q" name="q" class="form-control form-control-sm"
                       value="{{ request('q') }}" placeholder="Title or body…">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-dark btn-sm">Search</button>
            </div>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <caption class="visually-hidden">All community questions</caption>
            <thead class="table-light">
                <tr>
                    <th scope="col">Question</th>
                    <th scope="col">Category</th>
                    <th scope="col">Answers</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($questions as $question)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <a href="{{ route('questions.show', $question) }}"
                               class="fw-semibold text-decoration-none d-block">{{ $question->title }}</a>
                            <span class="small text-muted">
                                by {{ $question->user->name }} &middot; {{ $question->created_at->format('d M Y') }}
                            </span>
                        </th>
                        <td class="small">{{ $question->category?->name ?? '—' }}</td>
                        <td>{{ $question->answers_count }}</td>
                        <td>
                            <span class="badge {{ $question->is_closed ? 'text-bg-secondary' : 'text-bg-success' }}">
                                {{ $question->is_closed ? 'Closed' : 'Open' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <form method="POST" action="{{ route('admin.questions.toggle', $question) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        {{ $question->is_closed ? 'Re-open' : 'Close' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.questions.destroy', $question) }}"
                                      onsubmit="return confirm('Delete this question permanently?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">@include('partials.empty', ['icon' => 'patch-question', 'title' => 'No questions found'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Question list pages" class="mt-4">
    {{ $questions->links() }}
</nav>
@endsection
