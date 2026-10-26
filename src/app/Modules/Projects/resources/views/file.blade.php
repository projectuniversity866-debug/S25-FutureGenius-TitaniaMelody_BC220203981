@extends('layouts.app')
@section('title', $file->name)
@section('subtitle', $project->name)

@section('content')
<x-page-header :title="$file->name" :subtitle="$file->versions_count.' '.\Illuminate\Support\Str::plural('version', $file->versions_count).' · added by '.($file->creator?->name ?? 'a former member')"
               :breadcrumbs="['Projects' => route('projects.index'), $project->name => route('projects.show', $project), 'Files' => route('projects.show', $project).'#files', $file->name => null]">
    @can('deleteFile', [$project, $file])
        <x-slot:actions>
            <form method="POST" action="{{ route('projects.files.destroy', [$project, $file]) }}"
                  data-confirm-title="Delete “{{ $file->name }}”?" data-confirm="All {{ $file->versions_count }} versions are deleted for every member."
                  data-confirm-button="Delete file">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1" aria-hidden="true"></i> Delete</button>
            </form>
        </x-slot:actions>
    @endcan
</x-page-header>

<div class="row g-4">
    <div class="col-lg-7">
        <x-card title="Version history" icon="clock-history" flush>
            <ol class="list-group list-group-flush" reversed>
                @foreach ($file->versions as $version)
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-2">
                        <span class="badge {{ $loop->first ? 'text-bg-success' : 'text-bg-light' }}">v{{ $version->number }}{{ $loop->first ? ' · latest' : '' }}</span>
                        <div class="min-w-0 flex-grow-1">
                            <span class="d-block text-truncate aq-text-sm">{{ $version->original_name }} · {{ $version->readableSize() }}</span>
                            <span class="aq-text-xs aq-muted">
                                {{ $version->uploader?->name ?? 'a former member' }} · {{ $version->created_at->format('d M Y H:i') }}
                                @if ($version->note) · “{{ $version->note }}”@endif
                            </span>
                        </div>
                        <a href="{{ route('projects.files.download', [$project, $file, $version]) }}" class="btn btn-sm btn-quiet">
                            <i class="bi bi-download me-1" aria-hidden="true"></i> Download<span class="visually-hidden"> version {{ $version->number }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>

    <div class="col-lg-5">
        @can('contribute', $project)
            <x-card title="Upload a new version" icon="upload">
                <form method="POST" action="{{ route('projects.files.versions.store', [$project, $file]) }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">File <span class="aq-muted fw-normal">(up to 10 MB)</span></label>
                        <input type="file" id="file" name="file" class="form-control @error('file') is-invalid @enderror">
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">What changed? <span class="aq-muted fw-normal">(optional)</span></label>
                        <input type="text" id="note" name="note" maxlength="255" class="form-control" placeholder="e.g. Added the evaluation chapter">
                    </div>
                    <button type="submit" class="btn btn-accent btn-sm">Upload version {{ $file->versions_count + 1 }}</button>
                </form>
            </x-card>
        @else
            <p class="aq-text-sm aq-muted">{{ $project->isArchived() ? 'The project is archived, so files cannot change.' : 'Viewers can download files but not upload them.' }}</p>
        @endcan
    </div>
</div>
@endsection
