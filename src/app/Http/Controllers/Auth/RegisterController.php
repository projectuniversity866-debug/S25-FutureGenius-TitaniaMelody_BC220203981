<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * FR1 "User registration and authentication" / prototype module #1.
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        // Self-registration is limited to community roles; only an
        // administrator can promote somebody to Administrator.
        $roles = Role::whereIn('slug', [Role::MEMBER, Role::EXPERT])->get();

        return view('auth.register', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'confirmed', Password::min(8)],
            'role_slug' => ['required', 'string', 'in:'.Role::MEMBER.','.Role::EXPERT],
            'headline'  => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $role = Role::where('slug', $validated['role_slug'])->firstOrFail();

            $user = User::create([
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'password'  => $validated['password'],   // hashed by the model cast
                'role_id'   => $role->id,
                'is_active' => true,
            ]);

            // Every user gets an (empty) profile row straight away, so the
            // profile module never has to deal with a missing relation.
            Profile::create([
                'user_id'  => $user->id,
                'headline' => $validated['headline'] ?? null,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Welcome to AQDesk, '.$user->name.'!');
    }
}
