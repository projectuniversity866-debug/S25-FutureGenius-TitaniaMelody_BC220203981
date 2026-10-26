{{-- Fields shared by "New event" and "Edit event".  $event is null when creating; $forms are the organizer's forms. --}}
@php
    use App\Modules\EventsMedia\Models\Event;
    $tz = old('timezone', $event?->timezone ?? 'Asia/Karachi');
    $local = fn ($key) => old($key, $event?->{$key} ? $event->local($event->{$key})->format('Y-m-d\TH:i') : '');
    $err = fn ($key) => $errors->has($key) ? $key.'_error' : '';
@endphp

<x-card title="About the event" icon="info-circle" class="mb-4">
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" id="title" name="title" value="{{ old('title', $event?->title) }}" maxlength="150" required
               class="form-control @error('title') is-invalid @enderror" @error('title') aria-describedby="title_error" @enderror>
        @error('title')<div id="title_error" class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="summary" class="form-label">Summary</label>
        <input type="text" id="summary" name="summary" value="{{ old('summary', $event?->summary) }}" maxlength="255" required
               class="form-control @error('summary') is-invalid @enderror" aria-describedby="summary_hint {{ $err('summary') }}">
        <div id="summary_hint" class="form-text">One sentence shown on the events list.</div>
        @error('summary')<div id="summary_error" class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Details <span class="aq-muted fw-normal">(optional)</span></label>
        <textarea id="description" name="description" rows="8" maxlength="10000" aria-describedby="description_hint"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $event?->description) }}</textarea>
        <div id="description_hint" class="form-text">Agenda, speakers, what to bring. Markdown works.</div>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</x-card>

<x-card title="When and where" icon="calendar-event" class="mb-4">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label for="starts_at" class="form-label">Starts</label>
            <input type="datetime-local" id="starts_at" name="starts_at" value="{{ $local('starts_at') }}" required
                   class="form-control @error('starts_at') is-invalid @enderror" @error('starts_at') aria-describedby="starts_at_error" @enderror>
            @error('starts_at')<div id="starts_at_error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="ends_at" class="form-label">Ends</label>
            <input type="datetime-local" id="ends_at" name="ends_at" value="{{ $local('ends_at') }}" required
                   class="form-control @error('ends_at') is-invalid @enderror" @error('ends_at') aria-describedby="ends_at_error" @enderror>
            @error('ends_at')<div id="ends_at_error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="timezone" class="form-label">Time zone</label>
            <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror">
                @foreach (Event::TIMEZONES as $value => $label)
                    <option value="{{ $value }}" @selected($tz === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <fieldset class="mb-3">
        <legend class="form-label">Format</legend>
        @foreach (Event::FORMATS as $value => [$label, $icon])
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="format" id="format_{{ $value }}" value="{{ $value }}"
                       @checked(old('format', $event?->format ?? 'in_person') === $value)>
                <label class="form-check-label" for="format_{{ $value }}"><i class="bi bi-{{ $icon }} me-1" aria-hidden="true"></i>{{ $label }}</label>
            </div>
        @endforeach
        @error('format')<div class="aq-text-sm aq-danger mt-1">{{ $message }}</div>@enderror
    </fieldset>
    <div class="mb-3">
        <label for="venue" class="form-label">Venue</label>
        <input type="text" id="venue" name="venue" value="{{ old('venue', $event?->venue) }}" maxlength="255"
               class="form-control @error('venue') is-invalid @enderror" aria-describedby="venue_hint {{ $err('venue') }}"
               placeholder="e.g. Room 4, Virtual University Lahore campus">
        <div id="venue_hint" class="form-text">For in-person and hybrid events.</div>
        @error('venue')<div id="venue_error" class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <fieldset class="aq-border-top pt-3">
        <legend class="form-label">Online meeting <span class="aq-muted fw-normal">(online and hybrid events)</span></legend>
        <div class="mb-3">
            <label for="meeting_url" class="form-label">Joining link</label>
            <input type="url" id="meeting_url" name="meeting_url" value="{{ old('meeting_url', $event?->meeting_url) }}" maxlength="500" inputmode="url"
                   class="form-control @error('meeting_url') is-invalid @enderror" aria-describedby="meeting_url_hint {{ $err('meeting_url') }}"
                   placeholder="https://zoom.us/j/…  ·  https://meet.google.com/…  ·  https://meet.jit.si/…">
            <div id="meeting_url_hint" class="form-text">Only people with a seat (and you) see it, from the event page, the reminders and their calendar file.</div>
            @error('meeting_url')<div id="meeting_url_error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="create_jitsi" name="create_jitsi" value="1" @checked(old('create_jitsi')) aria-describedby="create_jitsi_hint">
            <label class="form-check-label" for="create_jitsi">No link yet? Create a free Jitsi Meet room for me</label>
            <div id="create_jitsi_hint" class="form-text">Used only when the joining link is empty. Jitsi needs no account for you or your attendees.</div>
        </div>
        <div>
            <label for="meeting_notes" class="form-label">Joining notes <span class="aq-muted fw-normal">(optional)</span></label>
            <input type="text" id="meeting_notes" name="meeting_notes" value="{{ old('meeting_notes', $event?->meeting_notes) }}" maxlength="255"
                   class="form-control @error('meeting_notes') is-invalid @enderror" placeholder="e.g. Passcode 4821 · cameras optional">
            @error('meeting_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </fieldset>
</x-card>

<x-card title="Registration" icon="person-check" class="mb-4">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="capacity" class="form-label">Seats <span class="aq-muted fw-normal">(optional)</span></label>
            <input type="number" id="capacity" name="capacity" min="1" max="100000" value="{{ old('capacity', $event?->capacity) }}"
                   class="form-control @error('capacity') is-invalid @enderror" aria-describedby="capacity_hint {{ $err('capacity') }}">
            <div id="capacity_hint" class="form-text">Leave empty for no limit. When it is full, people join a waitlist and move up as seats free.</div>
            @error('capacity')<div id="capacity_error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="registration_closes_at" class="form-label">Registration closes <span class="aq-muted fw-normal">(optional)</span></label>
            <input type="datetime-local" id="registration_closes_at" name="registration_closes_at" value="{{ $local('registration_closes_at') }}"
                   class="form-control @error('registration_closes_at') is-invalid @enderror" aria-describedby="closes_hint {{ $err('registration_closes_at') }}">
            <div id="closes_hint" class="form-text">Otherwise registration closes when the event starts.</div>
            @error('registration_closes_at')<div id="registration_closes_at_error" class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <label for="form_id" class="form-label">Registration questions <span class="aq-muted fw-normal">(optional)</span></label>
    <select id="form_id" name="form_id" class="form-select @error('form_id') is-invalid @enderror" aria-describedby="form_hint {{ $err('form_id') }}">
        <option value="">None — one-click registration</option>
        @foreach ($forms as $form)
            <option value="{{ $form->id }}" @selected((int) old('form_id', $event?->form_id) === $form->id)>
                {{ $form->title }} ({{ $form->fields_count }} {{ \Illuminate\Support\Str::plural('question', $form->fields_count) }})
            </option>
        @endforeach
    </select>
    <div id="form_hint" class="form-text">
        Every registrant answers this form's questions (e.g. dietary needs, experience level). Build one in
        @can('forms.create')<a href="{{ route('forms.create') }}">My forms</a>@else My forms @endcan.
    </div>
    @error('form_id')<div id="form_id_error" class="invalid-feedback">{{ $message }}</div>@enderror
</x-card>
