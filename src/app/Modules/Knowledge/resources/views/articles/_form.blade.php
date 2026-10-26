{{-- Shared by articles/create and articles/edit. Two submit buttons: save as draft, or publish. --}}
@csrf
@isset($article)
    @method('PUT')
@endisset

<div class="row g-3">
    <div class="col-md-8">
        <label for="title" class="form-label">Title</label>
        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $article->title ?? '') }}" required minlength="10" maxlength="160">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="kind" class="form-label">Kind</label>
        <select id="kind" name="kind" class="form-select">
            @foreach (\App\Modules\Knowledge\Models\Article::KINDS as $value => $label)
                <option value="{{ $value }}" @selected(old('kind', $article->kind ?? 'article') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="category_id" class="form-label">Topic</label>
        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
            <option value="">No topic</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $article->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label for="summary" class="form-label">Summary</label>
        <textarea id="summary" name="summary" rows="2" maxlength="300" required
                  class="form-control @error('summary') is-invalid @enderror"
                  aria-describedby="summary_hint">{{ old('summary', $article->summary ?? '') }}</textarea>
        <div id="summary_hint" class="form-text">One or two sentences shown on the hub's cards.</div>
        @error('summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <x-vue name="CharacterCounter" :props="['for' => 'summary', 'min' => 20, 'max' => 300]" />
    </div>
    <div class="col-12">
        <label for="body" class="form-label">Text</label>
        <x-vue name="MarkdownEditor" :props="['for' => 'body', 'previewUrl' => route('markdown.preview')]" />
        <textarea id="body" name="body" rows="18" required minlength="100"
                  class="form-control @error('body') is-invalid @enderror">{{ old('body', $article->body ?? '') }}</textarea>
        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <x-vue name="CharacterCounter" :props="['for' => 'body', 'min' => 100]" />
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <button type="submit" name="action" value="publish" class="btn btn-accent">
        {{ isset($article) && $article->isPublished() ? 'Save and keep published' : 'Publish' }}
    </button>
    <button type="submit" name="action" value="draft" class="btn btn-quiet">
        {{ isset($article) && $article->isPublished() ? 'Unpublish (back to draft)' : 'Save as draft' }}
    </button>
</div>
