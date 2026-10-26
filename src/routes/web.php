<?php

use App\Http\Controllers\AccessibilityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SavedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application shell routes
|--------------------------------------------------------------------------
| Pages that span every module. Each module registers its own routes from
| app/Modules/<Module>/routes/web.php (see ModuleServiceProvider):
|
|   Identity   — register, login, logout, profile, admin users
|   Community  — questions, answers, votes, tags, bookmarks, admin questions
|   Careers    — jobs, applications, admin opportunities
*/

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

// Everything the member saved for later, across modules.
Route::get('/saved', SavedController::class)->middleware('auth')->name('saved.index');

// Public profile: who someone is (Identity) and what they contributed (Community).
Route::get('/users/{user}', PublicProfileController::class)->name('profile.show');

// Accessibility statement and display preferences (roadmap 2.7).
Route::get('/accessibility', [AccessibilityController::class, 'show'])->name('accessibility');
Route::post('/accessibility', [AccessibilityController::class, 'update'])->name('accessibility.update');

// The rules of the community and of the platform (Governance, roadmap 2.5 – 2.6).
Route::get('/guidelines', [PageController::class, 'legal'])->defaults('page', 'guidelines')->name('guidelines');
Route::get('/terms', [PageController::class, 'legal'])->defaults('page', 'terms')->name('terms');
Route::get('/privacy', [PageController::class, 'legal'])->defaults('page', 'privacy')->name('privacy');

// Modules not built yet — visible placeholders that show the full scope.
Route::get('/modules/{module}', [PageController::class, 'comingSoon'])
    ->whereIn('module', ['events', 'consulting', 'newsletter'])
    ->name('modules.coming-soon');
