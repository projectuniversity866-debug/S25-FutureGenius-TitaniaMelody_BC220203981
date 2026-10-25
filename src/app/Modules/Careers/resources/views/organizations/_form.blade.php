{{-- Shared by organizations/create and organizations/edit. --}}
@csrf
@isset($organization)
    @method('PUT')
@endisset

<div class="row g-3">
    <div class="col-md-7">
        <label for="name" class="form-label">Name</label>
        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $organization->name ?? '') }}" required minlength="2" maxlength="120"
               placeholder="e.g. CodeCraft Solutions">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-5">
        <label for="industry" class="form-label">Industry <span class="aq-muted fw-normal">(optional)</span></label>
        <input type="text" id="industry" name="industry" class="form-control @error('industry') is-invalid @enderror"
               value="{{ old('industry', $organization->industry ?? '') }}" maxlength="80" placeholder="Software, Higher education…">
        @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="tagline" class="form-label">Tagline <span class="aq-muted fw-normal">(optional, one line)</span></label>
        <input type="text" id="tagline" name="tagline" class="form-control @error('tagline') is-invalid @enderror"
               value="{{ old('tagline', $organization->tagline ?? '') }}" maxlength="160">
        @error('tagline')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="about" class="form-label">About <span class="aq-muted fw-normal">(optional)</span></label>
        <textarea id="about" name="about" rows="6" class="form-control @error('about') is-invalid @enderror"
                  maxlength="5000">{{ old('about', $organization->about ?? '') }}</textarea>
        @error('about')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-5">
        <label for="website" class="form-label">Website <span class="aq-muted fw-normal">(optional)</span></label>
        <input type="url" id="website" name="website" class="form-control @error('website') is-invalid @enderror"
               value="{{ old('website', $organization->website ?? '') }}" placeholder="https://…">
        @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="location" class="form-label">Location <span class="aq-muted fw-normal">(optional)</span></label>
        <input type="text" id="location" name="location" class="form-control"
               value="{{ old('location', $organization->location ?? '') }}" placeholder="Lahore, Pakistan">
    </div>

    <div class="col-md-3">
        <label for="size" class="form-label">Size</label>
        <select id="size" name="size" class="form-select @error('size') is-invalid @enderror">
            <option value="">Not stated</option>
            @foreach (\App\Modules\Careers\Models\Organization::SIZES as $size)
                <option value="{{ $size }}" @selected(old('size', $organization->size ?? '') === $size)>{{ $size }} people</option>
            @endforeach
        </select>
        @error('size')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
