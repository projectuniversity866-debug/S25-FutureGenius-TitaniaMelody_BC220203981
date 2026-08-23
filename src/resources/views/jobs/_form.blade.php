{{-- Shared by jobs/create and jobs/edit. --}}
@csrf
@isset($job)
    @method('PUT')
@endisset

<div class="row g-3">
    <div class="col-md-8">
        <label for="title" class="form-label">Title</label>
        <input type="text" id="title" name="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $job->title ?? '') }}" required minlength="5" maxlength="255"
               placeholder="e.g. Junior Laravel Developer">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="type" class="form-label">Type</label>
        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="job" @selected(old('type', $job->type ?? 'job') === 'job')>Job</option>
            <option value="internship" @selected(old('type', $job->type ?? '') === 'internship')>Internship</option>
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="company" class="form-label">Company / Organisation</label>
        <input type="text" id="company" name="company"
               class="form-control @error('company') is-invalid @enderror"
               value="{{ old('company', $job->company ?? '') }}" required>
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

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" rows="7"
                  class="form-control @error('description') is-invalid @enderror"
                  required minlength="20">{{ old('description', $job->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="requirements" class="form-label">
            Requirements <span class="text-muted fw-normal">(optional)</span>
        </label>
        <textarea id="requirements" name="requirements" rows="5"
                  class="form-control">{{ old('requirements', $job->requirements ?? '') }}</textarea>
    </div>

    <div class="col-md-3">
        <label for="salary_min" class="form-label">Salary from (PKR)</label>
        <input type="number" id="salary_min" name="salary_min" class="form-control" min="0" step="1000"
               value="{{ old('salary_min', isset($job) && $job->salary_min ? (int) $job->salary_min : '') }}">
    </div>

    <div class="col-md-3">
        <label for="salary_max" class="form-label">Salary to (PKR)</label>
        <input type="number" id="salary_max" name="salary_max"
               class="form-control @error('salary_max') is-invalid @enderror" min="0" step="1000"
               value="{{ old('salary_max', isset($job) && $job->salary_max ? (int) $job->salary_max : '') }}">
        @error('salary_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="deadline" class="form-label">Application deadline</label>
        <input type="date" id="deadline" name="deadline"
               class="form-control @error('deadline') is-invalid @enderror"
               value="{{ old('deadline', $job->deadline?->format('Y-m-d') ?? '') }}">
        @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
