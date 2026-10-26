@extends('layouts.app')
@section('title', $user->name)
@section('subtitle', 'Member profile')

@section('content')
@php
    $tabUrl = fn ($t) => route('profile.show', $t === 'overview' ? $user : [$user, 'tab' => $t]);
    $earned = array_filter($badges, fn ($b) => $b['earned']);
@endphp

<div class="row g-4">
    {{-- Who they are --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <div class="d-flex justify-content-center mb-3">
                    <x-avatar :user="$user" size="xl" />
                </div>
                <h1 class="aq-h2">{{ $user->name }}</h1>
                @if ($user->profile?->headline)
                    <p class="aq-text-sm aq-muted mb-2">{{ $user->profile->headline }}</p>
                @endif
                <span class="badge text-bg-dark">{{ $user->roleName() }}</span>
                <x-verified-badge :user="$user" label class="d-block mt-2 aq-text-sm" />

                @if (auth()->id() === $user->id)
                    <a href="{{ route('profile.edit') }}" class="btn btn-quiet btn-sm w-100 mt-3">
                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit my profile
                    </a>
                @endif

                <hr class="aq-divider">

                <ul class="list-unstyled text-start mb-0 d-grid gap-2 aq-text-sm">
                    @if ($user->profile?->organization)
                        <li><i class="bi bi-building me-2 aq-muted" aria-hidden="true"></i>{{ $user->profile->organization }}</li>
                    @endif
                    @if ($user->profile?->location)
                        <li><i class="bi bi-geo-alt me-2 aq-muted" aria-hidden="true"></i>{{ $user->profile->location }}</li>
                    @endif
                    @foreach (['website' => ['globe', 'Website'], 'linkedin' => ['linkedin', 'LinkedIn'], 'github' => ['github', 'GitHub']] as $field => [$icon, $label])
                        @if ($user->profile?->{$field})
                            <li><i class="bi bi-{{ $icon }} me-2 aq-muted" aria-hidden="true"></i>
                                <a href="{{ $user->profile->{$field} }}" rel="noopener noreferrer" target="_blank">{{ $label }}<span class="visually-hidden"> (opens in a new tab)</span></a></li>
                        @endif
                    @endforeach
                    <li><i class="bi bi-calendar3 me-2 aq-muted" aria-hidden="true"></i>Member since {{ $user->created_at->format('M Y') }}</li>
                </ul>
            </div>
        </div>

        <x-card title="Reputation" icon="star" class="mb-3">
            <p class="aq-rep mb-2"><strong>{{ number_format($reputation['points']) }}</strong> points</p>
            <dl class="aq-dl">
                @foreach ($reputation['breakdown'] as $label => $points)
                    <dt>{{ $label }}</dt>
                    <dd class="{{ $points < 0 ? 'aq-danger' : '' }}">{{ $points > 0 ? '+' : '' }}{{ $points }}</dd>
                @endforeach
            </dl>

            <h3 class="aq-label mt-3 mb-2">Privileges</h3>
            <ul class="aq-privileges">
                @foreach ($privileges as $privilege)
                    <li @class(['is-unlocked' => $privilege['unlocked']])>
                        <i class="bi bi-{{ $privilege['unlocked'] ? 'unlock' : 'lock' }}" aria-hidden="true"></i>
                        <span>{{ $privilege['label'] }}</span>
                        <small>{{ $privilege['unlocked'] ? 'Unlocked' : $privilege['points'].' needed' }}</small>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <x-card :title="'Badges · '.count($earned).' of '.count($badges)" icon="award">
            <ul class="aq-badges">
                @foreach ($badges as $badge)
                    <li @class(['is-earned' => $badge['earned']])>
                        <span class="aq-badge-icon is-{{ $badge['tier'] }}" aria-hidden="true"><i class="bi bi-{{ $badge['icon'] }}"></i></span>
                        <span class="min-w-0">
                            <strong>{{ $badge['name'] }}</strong>
                            <span class="visually-hidden">{{ $badge['earned'] ? '(earned, '.$badge['tier'].')' : '(not yet earned)' }}</span>
                            <small>{{ $badge['description'] }}</small>
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    {{-- What they contributed --}}
    <div class="col-lg-8">
        <h2 class="visually-hidden">Contributions</h2>
        <div class="row g-2 mb-3">
            <div class="col-4"><x-stat label="Questions" :value="$user->questions_count" icon="patch-question" tone="blue" /></div>
            <div class="col-4"><x-stat label="Answers" :value="$user->answers_count" icon="chat-left-text" tone="green" /></div>
            <div class="col-4"><x-stat label="Accepted" :value="$accepted" icon="patch-check" /></div>
        </div>

        <x-tabs label="Profile sections" :items="[
            ['label' => 'Overview', 'url' => $tabUrl('overview'), 'active' => $tab === 'overview'],
            ['label' => 'Questions', 'url' => $tabUrl('questions'), 'active' => $tab === 'questions', 'count' => $user->questions_count],
            ['label' => 'Answers', 'url' => $tabUrl('answers'), 'active' => $tab === 'answers', 'count' => $user->answers_count],
            ['label' => 'Activity', 'url' => $tabUrl('activity'), 'active' => $tab === 'activity'],
        ]" />

        @if ($tab === 'overview')
            @if ($user->profile?->bio)
                <x-card title="About" class="mb-3">
                    <p class="mb-0 prose">{{ $user->profile->bio }}</p>
                </x-card>
            @endif

            @if ($user->profile && count($user->profile->skillsArray()))
                <x-card title="Expertise" class="mb-3">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($user->profile->skillsArray() as $skill)
                            <span class="badge text-bg-info">{{ $skill }}</span>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <x-card title="Top questions" flush class="h-100">
                        <ul class="list-group list-group-flush">
                            @forelse ($topQuestions as $q)
                                <li class="list-group-item">
                                    <a href="{{ route('questions.show', $q) }}" class="text-decoration-none fw-semibold">{{ $q->title }}</a>
                                    <span class="d-block aq-text-xs aq-muted">{{ $q->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $q->answers_count) }} · {{ $q->created_at->diffForHumans() }}</span>
                                </li>
                            @empty
                                <li class="list-group-item"><x-empty icon="patch-question" title="No questions yet" /></li>
                            @endforelse
                        </ul>
                    </x-card>
                </div>
                <div class="col-md-6">
                    <x-card title="Top answers" flush class="h-100">
                        <ul class="list-group list-group-flush">
                            @forelse ($topAnswers as $a)
                                <li class="list-group-item">
                                    <a href="{{ route('questions.show', $a->question) }}#answer-{{ $a->id }}" class="text-decoration-none fw-semibold">{{ $a->question->title }}</a>
                                    <span class="d-block aq-text-xs aq-muted">
                                        @if ($a->is_accepted)<span class="aq-accent-ink"><i class="bi bi-check2" aria-hidden="true"></i> Accepted</span> · @endif
                                        {{ $a->created_at->diffForHumans() }}
                                    </span>
                                </li>
                            @empty
                                <li class="list-group-item"><x-empty icon="chat-left-text" title="No answers yet" /></li>
                            @endforelse
                        </ul>
                    </x-card>
                </div>
            </div>

        @elseif ($tab === 'questions')
            @forelse ($questions as $question)
                @include('community::partials.question-card', ['question' => $question])
            @empty
                <div class="card"><x-empty icon="patch-question" title="No questions posted yet" /></div>
            @endforelse
            <nav aria-label="Question pages" class="mt-3">{{ $questions->links() }}</nav>

        @elseif ($tab === 'answers')
            <div class="card">
                <ul class="list-group list-group-flush">
                    @forelse ($answers as $answer)
                        <li class="list-group-item d-flex gap-3">
                            <span class="aq-stat-box text-center flex-shrink-0 {{ $answer->is_accepted ? 'is-accepted' : '' }}">
                                <strong>{{ $answer->score() }}</strong>
                                <span>{{ \Illuminate\Support\Str::plural('vote', abs($answer->score())) }}</span>
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('questions.show', $answer->question) }}#answer-{{ $answer->id }}" class="fw-semibold text-decoration-none">{{ $answer->question->title }}</a>
                                @if ($answer->is_accepted)<span class="badge text-bg-info ms-1">Accepted</span>@endif
                                <p class="mb-0 aq-text-sm aq-muted">{{ \Illuminate\Support\Str::limit(strip_tags(\Illuminate\Support\Str::markdown($answer->body)), 140) }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item"><x-empty icon="chat-left-text" title="No answers yet" /></li>
                    @endforelse
                </ul>
            </div>
            <nav aria-label="Answer pages" class="mt-3">{{ $answers->links() }}</nav>

        @else
            <x-card title="Reputation changes" icon="star" flush class="mb-3">
                <ul class="list-group list-group-flush">
                    @forelse ($ledger as $event)
                        <li class="list-group-item d-flex align-items-center gap-3">
                            <span @class(['aq-rep-delta', 'is-negative' => $event->points < 0])>{{ $event->points > 0 ? '+' : '' }}{{ $event->points }}</span>
                            <span class="flex-grow-1 aq-text-sm">{{ $event->label() }}@if ($event->actor) <span class="aq-muted">by {{ $event->actor->name }}</span>@endif</span>
                            <time class="aq-text-xs aq-muted" datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->diffForHumans() }}</time>
                        </li>
                    @empty
                        <li class="list-group-item"><x-empty icon="star" title="No reputation changes yet" /></li>
                    @endforelse
                </ul>
            </x-card>

            <x-card title="Recent activity" flush>
                <ol class="aq-feed">
                    @forelse ($activity as $item)
                        <li>
                            <i class="bi bi-{{ $item['type'] === 'asked' ? 'patch-question' : ($item['accepted'] ? 'patch-check' : 'chat-left-text') }}" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <span class="aq-ink-2">{{ ucfirst($item['type']) }}{{ $item['accepted'] ? ' (accepted)' : '' }}:</span>
                                <a href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                                <time datetime="{{ $item['at']->toIso8601String() }}">{{ $item['at']->diffForHumans() }}</time>
                            </div>
                        </li>
                    @empty
                        <li class="aq-feed-empty"><x-empty icon="activity" title="No activity yet" /></li>
                    @endforelse
                </ol>
            </x-card>
        @endif
    </div>
</div>
@endsection
