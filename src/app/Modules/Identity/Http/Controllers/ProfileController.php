<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Http\Requests\UpdatePasswordRequest;
use App\Modules\Identity\Http\Requests\UpdateProfileRequest;
use App\Modules\Identity\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FR1 "Profile management".
 */
class ProfileController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function edit(Request $request): View
    {
        $user = $request->user()->load('profile');

        return view('identity::profile.edit', compact('user'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->profiles->update($request->user(), $request->validated(), $request->file('avatar'));

        return redirect()->route('profile.edit')->with('status', 'Your profile has been updated.');
    }

    /** Change password form handler (kept on the same profile screen). */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->profiles->changePassword($request->user(), $request->validated('password'));

        return redirect()->route('profile.edit')->with('status', 'Your password has been changed.');
    }
}
