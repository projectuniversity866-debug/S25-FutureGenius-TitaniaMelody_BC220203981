<?php

use App\Modules\Careers\Http\Controllers\Admin\OpportunityModerationController;
use App\Modules\Careers\Http\Controllers\CareerToolsController;
use App\Modules\Careers\Http\Controllers\JobApplicationController;
use App\Modules\Careers\Http\Controllers\JobPostController;
use App\Modules\Careers\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

/*
| Careers — FR5 Jobs and Opportunities. Loaded inside the "web" middleware
| group by CareersServiceProvider.
*/

// Browsable without an account; applying needs one.
Route::get('/jobs', [JobPostController::class, 'index'])->name('jobs.index');
Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');

// Applying and publishing need a confirmed email address.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/applications', [JobApplicationController::class, 'index'])->name('applications.index');
    Route::delete('/applications/{application}', [JobApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::put('/applications/{application}/status', [JobApplicationController::class, 'updateStatus'])->name('applications.status');
    Route::get('/applications/{application}/resume', [JobApplicationController::class, 'resume'])->name('applications.resume');
    Route::get('/applications/{application}', [JobApplicationController::class, 'show'])->name('applications.show');
    Route::post('/applications/{application}/notes', [JobApplicationController::class, 'storeNote'])->name('applications.notes.store');
    Route::delete('/application-notes/{note}', [JobApplicationController::class, 'destroyNote'])->name('applications.notes.destroy');

    // Career tools (1.15): saved opportunities and job alerts.
    Route::middleware('throttle:60,1,saves')->group(function () {
        Route::post('/jobs/{job}/save', [CareerToolsController::class, 'save'])->name('jobs.save');
        Route::delete('/jobs/{job}/save', [CareerToolsController::class, 'unsave'])->name('jobs.unsave');
    });
    Route::get('/job-alerts', [CareerToolsController::class, 'alerts'])->name('alerts.index');
    Route::post('/job-alerts', [CareerToolsController::class, 'storeAlert'])->name('alerts.store');
    Route::delete('/job-alerts/{alert}', [CareerToolsController::class, 'destroyAlert'])->name('alerts.destroy');

    Route::get('/jobs/{job}/apply', [JobApplicationController::class, 'create'])->name('jobs.apply');
    Route::post('/jobs/{job}/apply', [JobApplicationController::class, 'store'])->name('jobs.apply.store');

    // Publishing needs the opportunities.publish permission.
    Route::middleware('can:opportunities.publish')->group(function () {
        Route::get('/my-jobs', [JobPostController::class, 'mine'])->name('jobs.mine');
        Route::get('/jobs/create', [JobPostController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [JobPostController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [JobPostController::class, 'edit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [JobPostController::class, 'update'])->name('jobs.update');
        Route::delete('/jobs/{job}', [JobPostController::class, 'destroy'])->name('jobs.destroy');
        Route::get('/jobs/{job}/applicants', [JobPostController::class, 'applicants'])->name('jobs.applicants');

        Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
        Route::get('/organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    });

    Route::middleware('can:content.moderate')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/jobs', [OpportunityModerationController::class, 'index'])->name('jobs.index');
        Route::put('/jobs/{job}/toggle', [OpportunityModerationController::class, 'toggle'])->name('jobs.toggle');
        Route::delete('/jobs/{job}', [OpportunityModerationController::class, 'destroy'])->name('jobs.destroy');
    });
});

// Declared last so /jobs/create is matched before the {job} wildcard.
Route::get('/jobs/{job}', [JobPostController::class, 'show'])->name('jobs.show');
Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
