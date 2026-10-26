@extends('layouts.app')
@section('title', $thread->title)
@section('subtitle', $project->name)

@section('content')
<x-page-header :title="$thread->title"
               :breadcrumbs="['Projects' => route('projects.index'), $project->name => route('projects.show', $project), 'Feedback' => route('projects.threads.index', $project), $thread->title => null]">
    @can('manageThread', [$project, $thread])
        <x-slot:actions>
            <form method="POST" action="{{ route('projects.threads.resolve', [$project, $thread]) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="resolved" value="{{ $thread->isResolved() ? 0 : 1 }}">
                <button type="submit" class="btn btn-soft btn-sm">
                    @if ($thread->isResolved())
                        <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Reopen
                    @else
                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i> Mark as resolved
                    @endif
                </button>
            </form>
            <form method="POST" action="{{ route('projects.threads.destroy', [$project, $thread]) }}"
                  data-confirm-title="Delete this thread?" data-confirm="The thread and its {{ $thread->replies_count }} replies are deleted for every member." data-confirm-button="Delete thread">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete</button>
            </form>
        </x-slot:actions>
    @endcan
</x-page-header>

@include('projects::_archived')

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    @if ($thread->isResolved())
                        <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Resolved</span>
                    @else
                        <span class="badge text-bg-primary"><i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>Open</span>
                    @endif
                    @if ($thread->file)
                        <a href="{{ route('projects.files.show', [$project, $thread->file]) }}" class="badge text-bg-light text-decoration-none">
                            <i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>About {{ $thread->file->name }}
                        </a>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2 mb-3 aq-text-sm">
                    @if ($thread->author)<x-avatar :user="$thread->author" size="sm" />@endif
                    <span><strong>{{ $thread->author?->name ?? 'A former member' }}</strong> · <time datetime="{{ $thread->created_at->toIso8601String() }}">{{ $thread->created_at->format('d M Y H:i') }}</time></span>
                </div>
                <x-markdown :text="$thread->body" />
                @if ($thread->isResolved())
                    <p class="aq-text-xs aq-muted mb-0 mt-3">Resolved by {{ $thread->resolver?->name ?? 'a former member' }} {{ $thread->resolved_at?->diffForHumans() }}.</p>
                @endif
            </div>
        </div>

        <x-card title="Replies ({{ $thread->replies_count }})" icon="chat-dots" id="replies" flush>
            @if ($thread->replies->isEmpty())
                <p class="card-body aq-text-sm aq-muted mb-0">No replies yet.</p>
            @else
                <ol class="aq-reply-list">
                    @foreach ($thread->replies as $reply)
                        <li id="reply-{{ $reply->id }}">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2 aq-text-sm">
                                @if ($reply->author)<x-avatar :user="$reply->author" size="sm" />@endif
                                <span class="flex-grow-1"><strong>{{ $reply->author?->name ?? 'A former member' }}</strong> · <time datetime="{{ $reply->created_at->toIso8601String() }}">{{ $reply->created_at->diffForHumans() }}</time></span>
                                @can('deleteReply', [$project, $reply])
                                    <form method="POST" action="{{ route('projects.threads.replies.destroy', [$project, $thread, $reply]) }}"
                                          data-confirm-title="Delete this reply?" data-confirm="It is removed for every member." data-confirm-button="Delete reply">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="aq-comment-delete">Delete<span class="visually-hidden"> reply by {{ $reply->author?->name ?? 'a former member' }}</span></button>
                                    </form>
                                @endcan
                            </div>
                            <x-markdown :text="$reply->body" />
                        </li>
                    @endforeach
                </ol>
            @endif

            @can('discuss', $project)
                @if ($thread->isResolved())
                    <p class="card-body aq-border-top aq-text-sm aq-muted mb-0">This thread is resolved. Reopen it to reply.</p>
                @else
                    <form method="POST" action="{{ route('projects.threads.reply', [$project, $thread]) }}" class="card-body aq-border-top" novalidate>
                        @csrf
                        <label for="reply_body" class="form-label">Your reply</label>
                        <textarea id="reply_body" name="body" rows="4" maxlength="10000" required aria-describedby="reply_hint @error('body') reply_error @enderror"
                                  class="form-control @error('body') is-invalid @enderror">{{ old('body') }}</textarea>
                        <div id="reply_hint" class="form-text">Markdown works. Everyone in this thread is notified.</div>
                        @error('body')<div id="reply_error" class="invalid-feedback">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-accent btn-sm mt-2">Post reply</button>
                    </form>
                @endif
            @endcan
        </x-card>
    </div>
</div>
@endsection
