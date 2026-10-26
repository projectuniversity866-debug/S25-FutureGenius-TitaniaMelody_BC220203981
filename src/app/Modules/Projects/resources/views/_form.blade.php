{{-- Name, description and visibility — shared by create and edit. --}}
<div class="mb-3">
    <label for="name" class="form-label">Project name</label>
    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $project?->name) }}" required minlength="3" maxlength="120" placeholder="e.g. AQDesk usability study">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label for="description" class="form-label">What is it about? <span class="aq-muted fw-normal">(optional)</span></label>
    <textarea id="description" name="description" rows="4" maxlength="3000" class="form-control">{{ old('description', $project?->description) }}</textarea>
</div>
<fieldset class="mb-1">
    <legend class="form-label">Who can see it</legend>
    @foreach (\App\Modules\Projects\Models\Project::VISIBILITY as $value => [$label, $hint])
        <label class="d-flex align-items-start gap-2 p-2 mb-2 aq-choice" for="visibility_{{ $value }}">
            <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="visibility" id="visibility_{{ $value }}" value="{{ $value }}"
                   @checked(old('visibility', $project?->visibility ?? 'private') === $value)>
            <span>
                <span class="d-block fw-semibold aq-text">{{ $label }}</span>
                <span class="d-block aq-text-xs aq-muted">{{ $hint }}</span>
            </span>
        </label>
    @endforeach
</fieldset>
