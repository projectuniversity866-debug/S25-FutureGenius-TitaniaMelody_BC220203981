@extends('layouts.app')
@section('title', 'Moderate questions')
@section('subtitle', 'Administration')

@section('content')
<x-page-header title="Moderate questions" subtitle="Close a question to stop new answers, or delete it entirely." />

<x-filter-panel :action="route('admin.questions.index')" label="Search questions"
                :filters="['q' => 'Search']" :total="$questions->total()" noun="question">
    <div class="is-wide">
        <label for="q" class="form-label">Search</label>
        <input type="search" id="q" name="q" class="form-control form-control-sm"
               value="{{ request('q') }}" placeholder="Title or body…">
    </div>
</x-filter-panel>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
            <caption class="visually-hidden">All community questions</caption>
            <thead>
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
                            <span class="aq-text-xs aq-muted">
                                by {{ $question->user->name }} · {{ $question->created_at->format('d M Y') }}
                            </span>
                        </th>
                        <td data-label="Category"><span>{{ $question->category?->name ?? '—' }}</span></td>
                        <td data-label="Answers"><span class="aq-num">{{ $question->answers_count }}</span></td>
                        <td data-label="Status">
                            <span><x-status :value="$question->is_closed ? 'closed' : 'open'" /></span>
                        </td>
                        <td data-label="Actions" class="aq-cell-actions">
                            <div class="aq-actions">
                                <form method="POST" action="{{ route('admin.questions.toggle', $question) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-quiet">
                                        {{ $question->is_closed ? 'Re-open' : 'Close' }}<span class="visually-hidden"> “{{ $question->title }}”</span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.questions.destroy', $question) }}"
                                      data-confirm-title="Delete “{{ $question->title }}”?"
                                      data-confirm="The question and its {{ $question->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $question->answers_count) }} are permanently deleted. To stop new answers instead, close it."
                                      data-confirm-button="Delete question">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete<span class="visually-hidden"> “{{ $question->title }}”</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty icon="patch-question" title="No questions found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Question list pages" class="mt-4">
    {{ $questions->links() }}
</nav>
@endsection
