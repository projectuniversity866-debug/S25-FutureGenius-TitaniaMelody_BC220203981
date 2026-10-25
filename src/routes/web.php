<?php

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

// Modules not built yet — visible placeholders that show the full scope.
Route::get('/modules/{module}', [PageController::class, 'comingSoon'])
    ->whereIn('module', ['projects', 'events', 'consulting', 'newsletter'])
    ->name('modules.coming-soon');
