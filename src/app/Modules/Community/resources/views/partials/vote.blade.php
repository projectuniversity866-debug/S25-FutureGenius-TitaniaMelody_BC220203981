{{-- Score and vote buttons for a question or answer.

     @include('community::partials.vote', ['post' => $answer, 'url' => route('votes.answer', $answer), 'noun' => 'answer'])

     Signed-in members get the VoteControl island, which votes without a page
     reload (roadmap 1.11); the two forms inside it are the no-JavaScript
     version. Visitors see the score only. --}}
@php
    $score = $post->score();
    $myVote = $post->voteOf(auth()->user())?->value;
    $voteWord = \Illuminate\Support\Str::plural('vote', abs($score));
@endphp

@auth
    <x-vue name="VoteControl" class="aq-vote" :props="['url' => $url, 'score' => $score, 'vote' => $myVote, 'noun' => $noun]">
        <form method="POST" action="{{ $url }}">
            @csrf
            <input type="hidden" name="value" value="1">
            <button type="submit" class="vote-btn {{ $myVote === 1 ? 'is-active' : '' }}"
                    aria-label="Upvote this {{ $noun }}" aria-pressed="{{ $myVote === 1 ? 'true' : 'false' }}">
                <i class="bi bi-caret-up-fill" aria-hidden="true"></i>
            </button>
        </form>

        <div class="aq-vote-score">{{ $score }}<span class="visually-hidden"> {{ $voteWord }}</span></div>

        <form method="POST" action="{{ $url }}">
            @csrf
            <input type="hidden" name="value" value="-1">
            <button type="submit" class="vote-btn {{ $myVote === -1 ? 'is-active' : '' }}"
                    aria-label="Downvote this {{ $noun }}" aria-pressed="{{ $myVote === -1 ? 'true' : 'false' }}">
                <i class="bi bi-caret-down-fill" aria-hidden="true"></i>
            </button>
        </form>
    </x-vue>
@else
    <div class="aq-vote">
        <div class="aq-vote-score">{{ $score }}<span class="visually-hidden"> {{ $voteWord }}</span></div>
    </div>
@endauth
