<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * FR1 "Profile management" / prototype module #4 "User Profile".
 */
class ProfileController extends Controller
{
    /** Public profile of any member. */
    public function show(User $user): View
    {
        $user->load(['role', 'profile']);
        $user->loadCount(['questions', 'answers']);

        $questions = $user->questions()->withCount('answers')->latest()->take(10)->get();

        return view('profile.show', compact('user', 'questions'));
    }

    public function edit(Request $request): View
    {
        $user = $request->user()->load('profile');

        return view('profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'headline'     => ['nullable', 'string', 'max:255'],
            'bio'          => ['nullable', 'string', 'max:2000'],
            'skills'       => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'location'     => ['nullable', 'string', 'max:255'],
            'website'      => ['nullable', 'url', 'max:255'],
            'linkedin'     => ['nullable', 'url', 'max:255'],
            'github'       => ['nullable', 'url', 'max:255'],
            'avatar'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        $profile = $user->profile ?: new Profile(['user_id' => $user->id]);

        $profile->fill([
            'headline'     => $validated['headline'] ?? null,
            'bio'          => $validated['bio'] ?? null,
            'skills'       => $validated['skills'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'location'     => $validated['location'] ?? null,
            'website'      => $validated['website'] ?? null,
            'linkedin'     => $validated['linkedin'] ?? null,
            'github'       => $validated['github'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            // Remove the previous file so uploads do not pile up.
            if ($profile->avatar_path) {
                Storage::disk('public')->delete($profile->avatar_path);
            }

            $profile->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $profile->user_id = $user->id;
        $profile->save();

        return redirect()->route('profile.edit')->with('status', 'Your profile has been updated.');
    }

    /** Change password form handler (kept on the same profile screen). */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($validated['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $request->user()->update(['password' => $validated['password']]);

        return redirect()->route('profile.edit')->with('status', 'Your password has been changed.');
    }
}
