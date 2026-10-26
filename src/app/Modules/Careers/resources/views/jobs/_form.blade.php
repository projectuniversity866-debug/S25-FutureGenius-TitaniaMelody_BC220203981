{{-- Shared by jobs/create and jobs/edit. $organizations = the poster's organization profiles. --}}
@csrf
@isset($job)
    @method('PUT')
@endisset

@php
    $selectedType = old('type', $job->type ?? 'job');
    $selectedOrg = (string) old('organization_id', $job->organization_id ?? ($organizations->count() === 1 ? $organizations->first()->id : ''));
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label for="title" class="form-label">Title</label>
        <input type="text" id="title" name="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $job->title ?? '') }}" required minlength="5" maxlength="255"
               placeholder="e.g. Junior Laravel Developer, or Women in Tech Scholarship 2026">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="type" class="form-label">Type</label>
        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
            @foreach (\App\Modules\Careers\Models\JobPost::typeGroups() as $group => $types)
                <optgroup label="{{ $group }}">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        @if ($organizations->isNotEmpty())
            <label for="organization_id" class="form-label">Organization</label>
            <select id="organization_id" name="organization_id"
                    class="form-select @error('organization_id') is-invalid @enderror" aria-describedby="organization_hint">
                <option value="">None — type a name below</option>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}" @selected($selectedOrg === (string) $organization->id)>{{ $organization->name }}</option>
                @endforeach
            </select>
            @error('organization_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div id="organization_hint" class="form-text">The opportunity appears on that organization’s profile.</div>
        @else
            <p class="form-label mb-1">Organization</p>
            <p class="aq-text-sm aq-muted mb-0">
                <a href="{{ route('organizations.create') }}">Create an organization profile</a>
                to give your opportunities a home page, or just type the name below.
            </p>
        @endif
    </div>

    <div class="col-md-6">
        <label for="company" class="form-label">
            Organization name @if ($organizations->isNotEmpty())<span class="aq-muted fw-normal">(if none is chosen)</span>@endif
        </label>
        <input type="text" id="company" name="company"
               class="form-control @error('company') is-invalid @enderror"
               value="{{ old('company', $job->company ?? '') }}" maxlength="255">
        @error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="work_mode" class="form-label">Work mode</label>
        <select id="work_mode" name="work_mode" class="form-select" required>
            <option value="onsite" @selected(old('work_mode', $job->work_mode ?? 'onsite') === 'onsite')>On-site</option>
            <option value="remote" @selected(old('work_mode', $job->work_mode ?? '') === 'remote')>Remote</option>
            <option value="hybrid" @selected(old('work_mode', $job->work_mode ?? '') === 'hybrid')>Hybrid</option>
        </select>
    </div>

    <div class="col-md-3">
        <label for="location" class="form-label">Location</label>
        <input type="text" id="location" name="location" class="form-control"
               value="{{ old('location', $job->location ?? '') }}" placeholder="Lahore, Pakistan">
    </div>

    <div class="col-md-6">
        <label for="external_url" class="form-label">
            More information <span class="aq-muted fw-normal">(optional link)</span>
        </label>
        <input type="url" id="external_url" name="external_url"
               class="form-control @error('external_url') is-invalid @enderror"
               value="{{ old('external_url', $job->external_url ?? '') }}" placeholder="https://…">
        @error('external_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><hr class="aq-divider my-1"></div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" rows="7"
                  class="form-control @error('description') is-invalid @enderror"
                  required minlength="20">{{ old('description', $job->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="requirements" class="form-label">
            Requirements <span class="aq-muted fw-normal">(optional)</span>
        </label>
        <textarea id="requirements" name="requirements" rows="5"
                  class="form-control">{{ old('requirements', $job->requirements ?? '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label for="eligibility" class="form-label">
            Who can apply <span class="aq-muted fw-normal">(optional)</span>
        </label>
        <textarea id="eligibility" name="eligibility" rows="5"
                  class="form-control @error('eligibility') is-invalid @enderror"
                  placeholder="e.g. Final-year students in Pakistan; teams of 2–4">{{ old('eligibility', $job->eligibility ?? '') }}</textarea>
        @error('eligibility')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12"><hr class="aq-divider my-1"></div>

    <fieldset class="col-12">
        <legend class="form-label mb-2">Money <span class="aq-muted fw-normal">— salary, stipend, award, funding or prize pool (optional)</span></legend>
        <div class="row g-3">
            <div class="col-md-3">
                <label for="salary_min" class="form-label">From (PKR)</label>
                <input type="number" id="salary_min" name="salary_min" class="form-control" min="0" step="1000"
                       value="{{ old('salary_min', isset($job) && $job->salary_min ? (int) $job->salary_min : '') }}">
            </div>
            <div class="col-md-3">
                <label for="salary_max" class="form-label">To (PKR)</label>
                <input type="number" id="salary_max" name="salary_max"
                       class="form-control @error('salary_max') is-invalid @enderror" min="0" step="1000"
                       value="{{ old('salary_max', isset($job) && $job->salary_max ? (int) $job->salary_max : '') }}">
                @error('salary_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </fieldset>

    <fieldset class="col-12">
        <legend class="form-label mb-2">Dates</legend>
        <div class="row g-3">
            <div class="col-md-3">
                <label for="deadline" class="form-label">Application deadline</label>
                <input type="date" id="deadline" name="deadline"
                       class="form-control @error('deadline') is-invalid @enderror"
                       value="{{ old('deadline', isset($job) ? $job->deadline?->format('Y-m-d') : '') }}">
                @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label for="starts_on" class="form-label">Starts <span class="aq-muted fw-normal">(event / programme)</span></label>
                <input type="date" id="starts_on" name="starts_on"
                       class="form-control @error('starts_on') is-invalid @enderror"
                       value="{{ old('starts_on', isset($job) ? $job->starts_on?->format('Y-m-d') : '') }}">
                @error('starts_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label for="ends_on" class="form-label">Ends</label>
                <input type="date" id="ends_on" name="ends_on"
                       class="form-control @error('ends_on') is-invalid @enderror"
                       value="{{ old('ends_on', isset($job) ? $job->ends_on?->format('Y-m-d') : '') }}">
                @error('ends_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @isset($job)
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               @checked(old('is_active', $job->is_active))>
                        <label class="form-check-label" for="is_active">Published</label>
                    </div>
                </div>
            @endisset
        </div>
    </fieldset>
</div>
