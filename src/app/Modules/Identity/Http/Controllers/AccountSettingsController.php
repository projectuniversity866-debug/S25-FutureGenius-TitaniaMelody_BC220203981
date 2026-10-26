<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * FR1 account settings: where you are signed in, which emails you get, and
 * who can see your profile.
 */
class AccountSettingsController extends Controller
{
    public function __construct(private SessionService $sessions) {}

    public function show(Request $request): View
    {
        return view('identity::settings.index', [
            'user'      => $request->user(),
            'sessions'  => $this->sessions->forUser($request->user(), $request->session()->getId()),
            'available' => $this->sessions->available(),
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_visibility' => ['required', Rule::in(['public', 'members'])],
        ]);

        $user = $request->user();
        $user->settings = array_merge($user->settings ?? [], [
            'profile_visibility' => $validated['profile_visibility'],
            'email_answers'      => $request->boolean('email_answers'),
            'email_applications' => $request->boolean('email_applications'),
            'email_follows'      => $request->boolean('email_follows'),
            'email_alerts'       => $request->boolean('email_alerts'),
            'email_projects'     => $request->boolean('email_projects'),
            'email_events'       => $request->boolean('email_events'),
            'email_consulting'   => $request->boolean('email_consulting'),
        ]);
        $user->save();

        return back()->with('status', 'Your settings have been saved.');
    }

    public function signOutOthers(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']],
            ['password.current_password' => 'That password is not correct.']);

        $count = $this->sessions->signOutOthers($request->user(), $request->input('password'), $request->session()->getId());

        return back()->with('status', $count
            ? 'Signed out of '.$count.' other '.Str::plural('device', $count).'.'
            : 'You are now signed out everywhere else.');
    }
}
