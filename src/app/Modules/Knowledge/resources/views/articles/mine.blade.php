@extends('layouts.app')
@section('title', 'My articles')
@section('subtitle', 'Knowledge Hub')

@section('content')
<x-page-header title="My articles" subtitle="Drafts are visible only to you until you publish them."
               :breadcrumbs="['Knowledge Hub' => route('articles.index'), 'My articles' => null]">
    @can('create', \App\Modules\Knowledge\Models\Article::class)
        <x-slot:actions>
            <a href="{{ route('articles.create') }}" class="btn btn-accent">
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> Write
            </a>
        </x-slot:actions>
    @endcan
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle aq-table">
            <caption class="visually-hidden">Your articles and guides</caption>
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Status</th>
                    <th scope="col">Views</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    <tr>
                        <th scope="row" class="fw-normal">
                            <a href="{{ route('articles.show', $article) }}" class="fw-semibold text-decoration-none d-block">{{ $article->title }}</a>
                            <span class="aq-text-xs aq-muted">{{ $article->kindLabel() }}{{ $article->category ? ' · '.$article->category->name : '' }} · edited {{ $article->updated_at->diffForHumans() }}</span>
                        </th>
                        <td data-label="Status"><span><x-status :value="$article->isPublished() ? 'published' : 'unpublished'" /></span></td>
                        <td data-label="Views"><span class="aq-num">{{ $article->views_count }}</span></td>
                        <td data-label="Actions" class="aq-cell-actions">
                            <div class="aq-actions">
                                <a href="{{ route('articles.edit', $article) }}" class="btn btn-sm btn-quiet">Edit<span class="visually-hidden"> “{{ $article->title }}”</span></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-empty icon="journal-richtext" title="You have not written anything yet" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<nav aria-label="Article pages" class="mt-4">{{ $articles->links() }}</nav>
@endsection
