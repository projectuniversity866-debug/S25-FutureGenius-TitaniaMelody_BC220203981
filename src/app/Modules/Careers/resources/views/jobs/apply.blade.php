@extends('layouts.app')
@section('title', 'Apply — '.$job->title)
@section('subtitle', 'Jobs & opportunities')
@section('container', 'aq-container-narrow')

@section('content')
<x-page-header title="Apply for this opportunity"
               :breadcrumbs="['Jobs & opportunities' => route('jobs.index'), $job->title => route('jobs.show', $job), 'Apply' => null]">
    <p>{{ $job->title }} at <strong class="aq-ink">{{ $job->company }}</strong></p>
</x-page-header>


<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('jobs.apply.store', $job) }}"
              enctype="multipart/form-data" novalidate>
            @csrf

            <div class="mb-3">
                <label for="cover_letter" class="form-label">Cover letter</label>
                <textarea id="cover_letter" name="cover_letter" rows="9"
                          class="form-control @error('cover_letter') is-invalid @enderror"
                          required minlength="30" maxlength="3000"
                          placeholder="Explain why you are a good fit for this role…"
                          aria-describedby="clHelp">{{ old('cover_letter') }}</textarea>
                <div id="clHelp" class="form-text">Between 30 and 3000 characters.</div>
                <x-vue name="CharacterCounter" :props="['for' => 'cover_letter', 'min' => 30, 'max' => 3000]" />
                @error('cover_letter')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label for="resume" class="form-label">
                    Résumé / CV <span class="aq-muted fw-normal">(optional)</span>
                </label>
                <input type="file" id="resume" name="resume"
                       class="form-control @error('resume') is-invalid @enderror"
                       accept=".pdf,.doc,.docx" aria-describedby="resumeHelp">
                <div id="resumeHelp" class="form-text">PDF, DOC or DOCX, up to 4 MB.</div>
                @error('resume')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent">
                    <i class="bi bi-send me-1" aria-hidden="true"></i> Submit application
                </button>
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-quiet">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
