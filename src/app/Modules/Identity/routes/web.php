<?php

use App\Modules\Identity\Http\Controllers\AccountSettingsController;
use App\Modules\Identity\Http\Controllers\Admin\RolePermissionController;
use App\Modules\Identity\Http\Controllers\Admin\UserController;
use App\Modules\Identity\Http\Controllers\Auth\EmailVerificationController;
use App\Modules\Identity\Http\Controllers\Auth\LoginController;
use App\Modules\Identity\Http\Controllers\Auth\PasswordResetController;
use App\Modules\Identity\Http\Controllers\Auth\RegisterController;
use App\Modules\Identity\Http\Controllers\Auth\SocialLoginController;
use App\Modules\Identity\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
| Identity — FR1 User Management. Loaded inside the "web" middleware group
| by IdentityServiceProvider.
*/

// Authentication (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    // Forgotten password
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.store');

    // Social login (Google, GitHub)
    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])
        ->whereIn('provider', ['google', 'github'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])
        ->whereIn('provider', ['google', 'github'])->name('social.callback');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    // Email verification
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');

    // Own profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Account settings: sessions, email preferences, privacy
    Route::get('/settings', [AccountSettingsController::class, 'show'])->name('settings.show');
    Route::put('/settings', [AccountSettingsController::class, 'updatePreferences'])->name('settings.update');
    Route::delete('/settings/sessions', [AccountSettingsController::class, 'signOutOthers'])
        ->middleware('throttle:6,1')->name('settings.sessions.destroy');

    // Role-based access: administrator user management
    Route::middleware('can:users.manage')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::put('/users/{user}/active', [UserController::class, 'toggleActive'])->name('users.active');

        Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::put('/roles/{role}', [RolePermissionController::class, 'update'])->name('roles.update');
    });
});
