<?php

use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobPostController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AQDesk routes
|--------------------------------------------------------------------------
| Grouped by the six mandatory prototype modules so the route file doubles
| as a map of the system:
|
|   1. Authentication      2. Role-Based Access   3. Dashboard
|   4. User Profile        5. Community Q&A       6. Jobs / Opportunities
|
| Modules deferred to the final project (Projects, Events & Podcasts,
| Consulting, Newsletter) are served by PageController::comingSoon().
*/

// ---------------------------------------------------------------------
// Public pages
// ---------------------------------------------------------------------
Route::get('/', [PageController::class, 'home'])->name('home');

// Community Q&A and the Jobs board are browsable without an account;
// posting, answering, voting and applying all require authentication.
Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');
Route::get('/jobs', [JobPostController::class, 'index'])->name('jobs.index');

// Deferred modules — visible placeholders that prove the modular design.
Route::get('/modules/{module}', [PageController::class, 'comingSoon'])
    ->whereIn('module', ['projects', 'events', 'consulting', 'newsletter'])
    ->name('modules.coming-soon');

// ---------------------------------------------------------------------
// Module 1 — Authentication  (guest only)
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ---------------------------------------------------------------------
// Authenticated area
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {

    // Module 3 — Dashboard (role-aware)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Module 4 — User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Module 5 — Community Q&A (write operations)
    Route::get('/questions/create', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

    Route::post('/questions/{question}/answers', [AnswerController::class, 'store'])->name('answers.store');
    Route::put('/answers/{answer}', [AnswerController::class, 'update'])->name('answers.update');
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy'])->name('answers.destroy');
    Route::post('/answers/{answer}/accept', [AnswerController::class, 'accept'])->name('answers.accept');

    Route::post('/questions/{question}/vote', [VoteController::class, 'question'])->name('votes.question');
    Route::post('/answers/{answer}/vote', [VoteController::class, 'answer'])->name('votes.answer');

    // Module 6 — Jobs / Opportunities
    Route::get('/applications', [JobApplicationController::class, 'index'])->name('applications.index');
    Route::delete('/applications/{application}', [JobApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::put('/applications/{application}/status', [JobApplicationController::class, 'updateStatus'])->name('applications.status');

    Route::get('/jobs/{job}/apply', [JobApplicationController::class, 'create'])->name('jobs.apply');
    Route::post('/jobs/{job}/apply', [JobApplicationController::class, 'store'])->name('jobs.apply.store');

    // Publishing opportunities is restricted to Administrators and Experts.
    Route::middleware('role:admin,expert')->group(function () {
        Route::get('/my-jobs', [JobPostController::class, 'mine'])->name('jobs.mine');
        Route::get('/jobs/create', [JobPostController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [JobPostController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [JobPostController::class, 'edit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [JobPostController::class, 'update'])->name('jobs.update');
        Route::delete('/jobs/{job}', [JobPostController::class, 'destroy'])->name('jobs.destroy');
        Route::get('/jobs/{job}/applicants', [JobPostController::class, 'applicants'])->name('jobs.applicants');
    });

    // ---------------------------------------------------------------------
    // Module 2 — Role-Based Access: the administrator-only area
    // ---------------------------------------------------------------------
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::put('/users/{user}/active', [UserController::class, 'toggleActive'])->name('users.active');

        Route::get('/questions', [ModerationController::class, 'questions'])->name('questions.index');
        Route::put('/questions/{question}/toggle', [ModerationController::class, 'toggleQuestion'])->name('questions.toggle');
        Route::delete('/questions/{question}', [ModerationController::class, 'destroyQuestion'])->name('questions.destroy');

        Route::get('/jobs', [ModerationController::class, 'jobs'])->name('jobs.index');
        Route::put('/jobs/{job}/toggle', [ModerationController::class, 'toggleJob'])->name('jobs.toggle');
        Route::delete('/jobs/{job}', [ModerationController::class, 'destroyJob'])->name('jobs.destroy');
    });
});

// ---------------------------------------------------------------------
// Public detail pages, declared last so that /questions/create and
// /jobs/create are matched before the {slug} wildcards.
// ---------------------------------------------------------------------
Route::get('/questions/{question}', [QuestionController::class, 'show'])->name('questions.show');
Route::get('/jobs/{job}', [JobPostController::class, 'show'])->name('jobs.show');
Route::get('/users/{user}', [ProfileController::class, 'show'])->name('profile.show');
