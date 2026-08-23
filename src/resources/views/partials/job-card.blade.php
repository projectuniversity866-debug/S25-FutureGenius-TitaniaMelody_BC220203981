{{-- One card in the jobs / internships grid. --}}
<div class="col-md-6 col-lg-4">
    <article class="card h-100 border-0 shadow-sm">
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge {{ $job->type === 'internship' ? 'text-bg-warning' : 'text-bg-primary' }}">
                    {{ ucfirst($job->type) }}
                </span>
                <span class="badge text-bg-light border text-capitalize">{{ $job->work_mode }}</span>
            </div>

            <h2 class="h6 mb-1">
                <a href="{{ route('jobs.show', $job) }}" class="text-decoration-none">{{ $job->title }}</a>
            </h2>

            <p class="text-muted small mb-2">
                <i class="bi bi-building" aria-hidden="true"></i> {{ $job->company }}
                @if ($job->location)
                    &middot; <i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $job->location }}
                @endif
            </p>

            <p class="small text-body-secondary flex-grow-1">
                {{ \Illuminate\Support\Str::words(strip_tags($job->description), 18) }}
            </p>

            <div class="d-flex justify-content-between align-items-center small text-muted border-top pt-2 mt-2">
                <span><i class="bi bi-cash-coin" aria-hidden="true"></i> {{ $job->salaryRange() }}</span>
                @if ($job->deadline)
                    <span class="{{ $job->isExpired() ? 'text-danger' : '' }}">
                        <i class="bi bi-clock" aria-hidden="true"></i> {{ $job->deadline->format('d M Y') }}
                    </span>
                @endif
            </div>
        </div>
    </article>
</div>
