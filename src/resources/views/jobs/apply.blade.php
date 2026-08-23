@extends('layouts.app')
@section('title', 'Apply — '.$job->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h4 fw-bold mb-1">Apply for this opportunity</h1>
        <p class="text-muted mb-4">
            {{ $job->title }} at <strong>{{ $job->company }}</strong>
        </p>

        <div class="card border-0 shadow-sm">
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
                        @error('cover_letter')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="resume" class="form-label">
                            Résumé / CV <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <input type="file" id="resume" name="resume"
                               class="form-control @error('resume') is-invalid @enderror"
                               accept=".pdf,.doc,.docx" aria-describedby="resumeHelp">
                        <div id="resumeHelp" class="form-text">PDF, DOC or DOCX, up to 4 MB.</div>
                        @error('resume')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-info fw-semibold">
                            <i class="bi bi-send" aria-hidden="true"></i> Submit application
                        </button>
                        <a href="{{ route('jobs.show', $job) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
