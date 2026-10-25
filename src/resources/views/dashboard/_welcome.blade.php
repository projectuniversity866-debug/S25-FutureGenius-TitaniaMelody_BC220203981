{{-- Greeting, role and the role's main actions — shared by every dashboard. --}}
<div class="card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <x-avatar :user="$user" size="lg" />

        <div class="flex-grow-1 min-w-0">
            <h1 class="aq-h2">Welcome back, {{ $user->name }}</h1>
            <p class="mb-0 d-flex flex-wrap align-items-center gap-2 aq-text-sm aq-muted">
                <span class="badge text-bg-dark">{{ $user->roleName() }}</span>
                <span>{{ $user->profile?->headline ?? 'Here is what is happening on AQDesk.' }}</span>
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('questions.create') }}" class="btn btn-accent btn-sm">
                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Ask a question
            </a>
            @can('create', \App\Modules\Careers\Models\JobPost::class)
                <a href="{{ route('jobs.create') }}" class="btn btn-quiet btn-sm">
                    <i class="bi bi-briefcase me-1" aria-hidden="true"></i> Post an opportunity
                </a>
            @endcan
            @can('users.manage')
                <a href="{{ route('admin.users.index') }}" class="btn btn-quiet btn-sm">
                    <i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Admin panel
                </a>
            @endcan
        </div>
    </div>
</div>

<h2 class="visually-hidden">Your numbers</h2>
<div class="row g-3 mb-4">
    @foreach ($tiles as $tile)
        <div class="col-6 col-xl-3">
            <x-stat :label="$tile['label']" :value="$tile['value']" :icon="$tile['icon']" :tone="$tile['tone']"
                    :delta="$tile['delta']" :trend="$tile['trend']" :hint="$tile['hint']" />
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <x-card title="To do" icon="list-check" flush class="h-100">
            <ul class="list-group list-group-flush">
                @forelse ($todo as $item)
                    <li class="list-group-item">
                        <a href="{{ $item['url'] }}" class="aq-todo">
                            <i class="bi bi-{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['text'] }}</span>
                            <i class="bi bi-chevron-right ms-auto" aria-hidden="true"></i>
                        </a>
                    </li>
                @empty
                    <li class="list-group-item">
                        <x-empty icon="check2-circle" title="You are all caught up" />
                    </li>
                @endforelse
            </ul>
        </x-card>
    </div>
    <div class="col-lg-6">
        <x-card title="Recent activity" icon="activity" flush class="h-100">
            @can('activity.view')
                <x-slot:actions><a href="{{ route('admin.activity.index') }}">Activity log</a></x-slot:actions>
            @else
                <x-slot:actions><a href="{{ route('notifications.index') }}">All notifications</a></x-slot:actions>
            @endcan
            <ol class="aq-feed">
                @forelse ($feed as $entry)
                    <li>
                        <i class="bi bi-{{ $entry['icon'] }}" aria-hidden="true"></i>
                        <div class="min-w-0">
                            @if ($entry['url'])
                                <a href="{{ $entry['url'] }}">{{ $entry['text'] }}</a>
                            @else
                                <span>{{ $entry['text'] }}</span>
                            @endif
                            <time datetime="{{ $entry['when']->toIso8601String() }}">{{ $entry['when']->diffForHumans() }}</time>
                        </div>
                    </li>
                @empty
                    <li class="aq-feed-empty"><x-empty icon="activity" title="Nothing has happened yet" /></li>
                @endforelse
            </ol>
        </x-card>
    </div>
</div>
