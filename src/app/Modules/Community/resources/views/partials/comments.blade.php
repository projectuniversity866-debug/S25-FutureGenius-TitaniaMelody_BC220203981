{{-- Comments under a question or answer, and the form to add one.
     @include('community::partials.comments', ['post' => $answer, 'storeRoute' => route('comments.answer', $answer)]) --}}
@php
    $me = auth()->user();
    $permission = $me ? \Illuminate\Support\Facades\Gate::inspect('create', [\App\Modules\Community\Models\Comment::class, $post]) : null;
    $label = class_basename($post) === 'Answer' ? 'answer' : 'question';
@endphp

<div class="aq-comments">
    @if ($post->comments->isNotEmpty())
        <ul class="aq-comment-list" aria-label="Comments on this {{ $label }}">
            @foreach ($post->comments as $comment)
                <li id="comment-{{ $comment->id }}">
                    <span class="aq-comment-body">{{ $comment->body }}</span>
                    <span class="aq-comment-meta">
                        — <a href="{{ route('profile.show', $comment->user) }}">{{ $comment->user->name }}</a>
                        <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time>
                    </span>
                    @if ($me?->can('delete', $comment))
                        <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="d-inline"
                              data-confirm-title="Delete this comment?" data-confirm="The comment is removed for everyone." data-confirm-button="Delete comment">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="aq-comment-delete">Delete<span class="visually-hidden"> comment by {{ $comment->user->name }}</span></button>
                        </form>
                    @endif
                    <x-report-link :item="$comment" class="aq-report-link-sm" />
                </li>
            @endforeach
        </ul>
    @endif

    @auth
        @if ($permission->allowed())
            <details class="aq-comment-add" @if ($errors->has('body') && old('comment_on') === $label.'-'.$post->id) open @endif>
                <summary>Add a comment</summary>
                <form method="POST" action="{{ $storeRoute }}">
                    @csrf
                    <input type="hidden" name="comment_on" value="{{ $label }}-{{ $post->id }}">
                    <label for="comment-{{ $label }}-{{ $post->id }}" class="visually-hidden">Comment on this {{ $label }}</label>
                    <textarea id="comment-{{ $label }}-{{ $post->id }}" name="body" rows="2" class="form-control form-control-sm"
                              maxlength="{{ \App\Modules\Community\Models\Comment::MAX_LENGTH }}" required minlength="5"
                              placeholder="Ask for clarification or suggest an improvement">{{ old('comment_on') === $label.'-'.$post->id ? old('body') : '' }}</textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="aq-text-xs aq-muted">Plain text, up to {{ \App\Modules\Community\Models\Comment::MAX_LENGTH }} characters.</span>
                        <button type="submit" class="btn btn-quiet btn-sm">Add comment</button>
                    </div>
                </form>
            </details>
        @else
            <p class="aq-text-xs aq-muted mb-0"><i class="bi bi-lock me-1" aria-hidden="true"></i>{{ $permission->message() }}</p>
        @endif
    @endauth
</div>
