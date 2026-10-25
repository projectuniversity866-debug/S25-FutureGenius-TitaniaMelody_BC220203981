@extends('layouts.app')
@section('title', 'Edit history — '.$question->title)
@section('subtitle', 'Community Q&A')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header :title="'Edit history of this '.$kind"
               :subtitle="count($versions).' '.\Illuminate\Support\Str::plural('version', count($versions)).', newest first.'"
               :breadcrumbs="['Community Q&A' => route('questions.index'), $question->title => $backUrl, 'History' => null]" />

<ol class="list-unstyled d-grid gap-3">
    @foreach ($versions as $i => $version)
        <li>
            <x-card :title="$version['current'] ? 'Current version' : 'Version '.(count($versions) - $i)">
                <x-slot:actions>
                    <span class="aq-text-xs aq-muted">
                        {{ $version['by']?->name ?? 'Unknown' }} ·
                        <time datetime="{{ $version['at']?->toIso8601String() }}">{{ $version['at']?->diffForHumans() }}</time>
                    </span>
                </x-slot:actions>

                @if ($version['titleChanged'])
                    <p class="aq-text-sm mb-2">
                        Title changed from <del class="aq-danger">{{ $version['titleChanged'] }}</del>
                        to <ins class="aq-diff-ins">{{ $version['title'] }}</ins>
                    </p>
                @endif

                @if ($version['diff'])
                    <h3 class="aq-label mb-2">Changes from the version before</h3>
                    <div class="aq-diff" role="table" aria-label="Line changes">
                        @foreach ($version['diff'] as $line)
                            <div class="aq-diff-line is-{{ $line['type'] }}" role="row">
                                <span class="aq-diff-sign" role="cell">
                                    <span aria-hidden="true">{{ ['added' => '+', 'removed' => '−', 'same' => ' '][$line['type']] }}</span>
                                    <span class="visually-hidden">{{ ['added' => 'Added:', 'removed' => 'Removed:', 'same' => 'Unchanged:'][$line['type']] }}</span>
                                </span>
                                <span role="cell">{{ $line['text'] === '' ? ' ' : $line['text'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <h3 class="aq-label mb-2">Original text</h3>
                    <x-markdown :text="$version['body']" />
                @endif
            </x-card>
        </li>
    @endforeach
</ol>

<a href="{{ $backUrl }}" class="btn btn-quiet btn-sm"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to the {{ $kind }}</a>
@endsection
