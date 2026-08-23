@extends('layouts.app')
@section('title', 'Edit profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 fw-bold mb-0">Edit profile</h1>
            <a href="{{ route('profile.show', $user) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-eye" aria-hidden="true"></i> View public profile
            </a>
        </div>

        {{-- Profile details --}}
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="details-heading">
            <div class="card-header bg-white">
                <h2 id="details-heading" class="h6 fw-bold mb-0">Profile details</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="d-flex align-items-center gap-3 mb-4">
                        @include('partials.avatar', ['user' => $user, 'size' => 72])
                        <div class="flex-grow-1">
                            <label for="avatar" class="form-label">Profile photo</label>
                            <input type="file" id="avatar" name="avatar" accept="image/*"
                                   class="form-control @error('avatar') is-invalid @enderror"
                                   aria-describedby="avatarHelp">
                            <div id="avatarHelp" class="form-text">JPG, PNG or WebP, up to 2 MB.</div>
                            @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="headline" class="form-label">Headline</label>
                            <input type="text" id="headline" name="headline" class="form-control"
                                   value="{{ old('headline', $user->profile?->headline) }}"
                                   placeholder="e.g. Final year Computer Science student">
                        </div>

                        <div class="col-12">
                            <label for="bio" class="form-label">About me</label>
                            <textarea id="bio" name="bio" rows="4" class="form-control"
                                      aria-describedby="bioHelp">{{ old('bio', $user->profile?->bio) }}</textarea>
                            <div id="bioHelp" class="form-text">A short introduction, up to 2000 characters.</div>
                        </div>

                        <div class="col-12">
                            <label for="skills" class="form-label">Skills</label>
                            <input type="text" id="skills" name="skills" class="form-control"
                                   value="{{ old('skills', $user->profile?->skills) }}"
                                   placeholder="PHP, Laravel, MySQL, Machine Learning"
                                   aria-describedby="skillsHelp">
                            <div id="skillsHelp" class="form-text">Separate each skill with a comma.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="organization" class="form-label">Organisation / University</label>
                            <input type="text" id="organization" name="organization" class="form-control"
                                   value="{{ old('organization', $user->profile?->organization) }}">
                        </div>

                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" id="location" name="location" class="form-control"
                                   value="{{ old('location', $user->profile?->location) }}"
                                   placeholder="Lahore, Pakistan">
                        </div>

                        <div class="col-md-4">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" id="website" name="website" class="form-control"
                                   value="{{ old('website', $user->profile?->website) }}"
                                   placeholder="https://">
                        </div>

                        <div class="col-md-4">
                            <label for="linkedin" class="form-label">LinkedIn</label>
                            <input type="url" id="linkedin" name="linkedin" class="form-control"
                                   value="{{ old('linkedin', $user->profile?->linkedin) }}"
                                   placeholder="https://linkedin.com/in/...">
                        </div>

                        <div class="col-md-4">
                            <label for="github" class="form-label">GitHub</label>
                            <input type="url" id="github" name="github" class="form-control"
                                   value="{{ old('github', $user->profile?->github) }}"
                                   placeholder="https://github.com/...">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-info fw-semibold">Save changes</button>
                    </div>
                </form>
            </div>
        </section>

        {{-- Password --}}
        <section class="card border-0 shadow-sm" aria-labelledby="password-heading">
            <div class="card-header bg-white">
                <h2 id="password-heading" class="h6 fw-bold mb-0">Change password</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label">Current password</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   required autocomplete="current-password">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label">New password</label>
                            <input type="password" id="new_password" name="password"
                                   class="form-control" required autocomplete="new-password">
                        </div>
                        <div class="col-md-4">
                            <label for="password_confirmation" class="form-label">Confirm new password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-outline-dark">Update password</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
