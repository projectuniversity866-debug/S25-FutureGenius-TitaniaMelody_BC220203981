{{-- Save-for-later toggle under a question's vote buttons.

     @include('community::partials.bookmark', ['question' => $question, 'saved' => $saved])

     A toggle button: the name stays "Save this question" and aria-pressed
     says whether it is saved. The form is the no-JavaScript version. --}}
@php $count = (int) ($question->bookmarks_count ?? $question->bookmarks()->count()); @endphp

@auth
    <x-vue name="BookmarkButton" class="aq-bookmark"
           :props="['url' => route('bookmarks.store', $question), 'saved' => $saved, 'count' => $count]">
        <form method="POST" action="{{ route($saved ? 'bookmarks.destroy' : 'bookmarks.store', $question) }}">
            @csrf
            @if ($saved) @method('DELETE') @endif
            <button type="submit" @class(['aq-bookmark-btn', 'is-active' => $saved])
                    aria-label="Save this question" aria-pressed="{{ $saved ? 'true' : 'false' }}" title="Save this question">
                <i class="bi bi-bookmark{{ $saved ? '-fill' : '' }}" aria-hidden="true"></i>
            </button>
        </form>
        @if ($count)
            <span class="aq-bookmark-count">{{ $count }}<span class="visually-hidden"> {{ $count === 1 ? 'person has' : 'people have' }} saved this</span></span>
        @endif
    </x-vue>
@endauth
