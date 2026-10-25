{{-- Shared by questions/create and questions/edit. --}}
@csrf
@isset($question)
    @method('PUT')
@endisset

<div class="mb-3">
    <label for="title" class="form-label">Question title</label>
    <input type="text" id="title" name="title"
           class="form-control @error('title') is-invalid @enderror"
           value="{{ old('title', $question->title ?? '') }}"
           required minlength="10" maxlength="255"
           placeholder="Be specific — imagine you are asking a colleague"
           aria-describedby="titleHelp">
    <div id="titleHelp" class="form-text">At least 10 characters.</div>
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="category_id" class="form-label">Category</label>
    <select id="category_id" name="category_id"
            class="form-select @error('category_id') is-invalid @enderror" required>
        <option value="">Choose a category…</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}"
                @selected((int) old('category_id', $question->category_id ?? 0) === $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="body" class="form-label">Details</label>
    <textarea id="body" name="body" rows="10"
              class="form-control @error('body') is-invalid @enderror"
              required minlength="20"
              placeholder="Describe what you have tried and what you expected to happen."
              aria-describedby="bodyHelp">{{ old('body', $question->body ?? '') }}</textarea>
    <div id="bodyHelp" class="form-text">At least 20 characters.</div>
    @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label for="tags" class="form-label">Tags <span style="color: var(--aq-muted); font-weight: 400;">(optional)</span></label>
    <input type="text" id="tags" name="tags" class="form-control"
           value="{{ old('tags', isset($question) ? $question->tags->pluck('name')->implode(', ') : '') }}"
           placeholder="laravel, mysql, accessibility"
           aria-describedby="tagsHelp">
    <div id="tagsHelp" class="form-text">Up to 5 tags, separated by commas.</div>
</div>
