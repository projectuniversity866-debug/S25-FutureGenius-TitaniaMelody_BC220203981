{{-- Title, summary, level and topic of a learning path. --}}
<div class="row g-3">
    <div class="col-md-8">
        <label for="title" class="form-label">Title</label>
        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $path?->title) }}" required minlength="10" maxlength="160" placeholder="e.g. Start with web accessibility">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="level" class="form-label">Level</label>
        <select id="level" name="level" class="form-select">
            @foreach (\App\Modules\Knowledge\Models\LearningResource::LEVELS as $key => $label)
                <option value="{{ $key }}" @selected(old('level', $path?->level) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label for="summary" class="form-label">Summary</label>
        <textarea id="summary" name="summary" rows="2" maxlength="300" required class="form-control @error('summary') is-invalid @enderror">{{ old('summary', $path?->summary) }}</textarea>
        @error('summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="category_id" class="form-label">Topic</label>
        <select id="category_id" name="category_id" class="form-select">
            <option value="">No topic</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected((string) old('category_id', $path?->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>
</div>
