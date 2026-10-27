{{-- Site announcements on every page (roadmap 3.18). Each is a labelled
     region so screen-reader users can find or skip it; the tone is given by
     an icon and a word, never by colour alone. --}}
@if (request()->hasSession())
    @foreach (app(\App\Modules\Communications\Services\AnnouncementService::class)->bannersFor(auth()->user(), request()->session()) as $a)
        @php [$toneLabel, $toneIcon, $toneClass] = \App\Modules\Communications\Models\Announcement::TONES[$a->tone]; @endphp
        <section class="alert {{ $toneClass }} d-flex align-items-start gap-2" aria-label="Announcement: {{ $a->title }}">
            <i class="bi bi-{{ $toneIcon }} mt-1" aria-hidden="true"></i>
            <div class="flex-grow-1 min-w-0">
                <strong>{{ $toneLabel }}: {{ $a->title }}</strong>
                <span class="d-block aq-text-sm">{{ $a->body }}
                    @if ($a->link_url) <a href="{{ $a->link_url }}" class="fw-semibold">{{ $a->link_label }}</a>@endif
                </span>
            </div>
            @if ($a->is_dismissible)
                <form method="POST" action="{{ route('announcements.dismiss', $a) }}">
                    @csrf
                    <button type="submit" class="btn-close" aria-label="Dismiss the announcement “{{ $a->title }}”"></button>
                </form>
            @endif
        </section>
    @endforeach
@endif
