<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use App\Modules\Identity\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * FR1 "User registration and authentication".
 */
class RegisterController extends Controller
{
    public function __construct(private RegistrationService $registration) {}

    public function create(): View
    {
        $roles = $this->registration->selfServiceRoles();

        return view('identity::auth.register', compact('roles'));
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->registration->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Welcome to AQDesk, '.$user->name.'!');
    }
}
