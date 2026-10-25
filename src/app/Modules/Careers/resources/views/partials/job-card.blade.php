{{-- One card in the jobs / internships grid. --}}
<div class="col-md-6 col-xl-4">
    <a href="{{ route('jobs.show', $job) }}" class="card aq-card-link h-100">
        <div class="card-body aq-job-card">
            <div class="d-flex align-items-start gap-3 mb-3">
                <x-org-mark :name="$job->company" />
                <div class="min-w-0 flex-grow-1">
                    <h2 class="aq-card-title text-truncate">{{ $job->title }}</h2>
                    <p class="mb-0 aq-text-sm aq-muted">
                        {{ $job->company }}@if ($job->location) · {{ $job->location }} @endif
                    </p>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge {{ $job->type === 'internship' ? 'text-bg-warning' : 'text-bg-primary' }}">
                    {{ ucfirst($job->type) }}
                </span>
                <span class="badge text-bg-light text-capitalize">{{ $job->work_mode }}</span>
            </div>

            <p class="flex-grow-1 mb-0 aq-text-sm aq-ink-2">
                {{ \Illuminate\Support\Str::words(strip_tags($job->description), 16) }}
            </p>

            <div class="aq-job-meta">
                <span><i class="bi bi-cash-coin me-1" aria-hidden="true"></i> {{ $job->salaryRange() }}</span>
                @if ($job->deadline)
                    <span class="{{ $job->isExpired() ? 'text-danger' : '' }}">
                        <i class="bi bi-clock me-1" aria-hidden="true"></i> {{ $job->deadline->format('d M Y') }}
                    </span>
                @endif
            </div>
        </div>
    </a>
</div>
