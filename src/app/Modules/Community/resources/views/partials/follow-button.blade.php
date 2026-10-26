{{-- Follow / unfollow a tag, with its follower count.

     @include('community::partials.follow-button', ['tag' => $tag, 'following' => true, 'count' => 3])

     The button says what it does ("Follow laravel"), or that you follow the
     tag ("Following laravel, select to unfollow"); its accessible name always
     contains the visible word. Visitors see the count, and a sign-in link
     when `signIn` is set. --}}
@php
    $count = (int) $count;
    $followers = $count.' '.\Illuminate\Support\Str::plural('follower', $count);
@endphp

@auth
    <x-vue name="FollowButton" class="aq-follow"
           :props="['url' => route('tags.follow', $tag), 'tag' => $tag->name, 'following' => $following, 'count' => $count]">
        <form method="POST" action="{{ route($following ? 'tags.unfollow' : 'tags.follow', $tag) }}">
            @csrf
            @if ($following) @method('DELETE') @endif
            <button type="submit" class="btn btn-sm {{ $following ? 'btn-soft' : 'btn-quiet' }}"
                    aria-label="{{ $following ? 'Following '.$tag->name.', select to unfollow' : 'Follow '.$tag->name }}">
                <i class="bi bi-{{ $following ? 'check-lg' : 'plus-lg' }} me-1" aria-hidden="true"></i>{{ $following ? 'Following' : 'Follow' }}
            </button>
        </form>
        <span class="aq-follow-count">{{ $followers }}</span>
    </x-vue>
@else
    <div class="aq-follow">
        @if ($signIn ?? false)
            <a href="{{ route('login') }}" class="btn btn-sm btn-quiet">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Sign in to follow
            </a>
        @endif
        <span class="aq-follow-count">{{ $followers }}</span>
    </div>
@endauth
