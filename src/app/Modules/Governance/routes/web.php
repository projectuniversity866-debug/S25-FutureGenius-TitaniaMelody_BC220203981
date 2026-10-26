<?php

use App\Modules\Governance\Http\Controllers\Admin\ModerationController;
use App\Modules\Governance\Http\Controllers\PrivacyController;
use App\Modules\Governance\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
| Governance — reports and moderation (2.5), privacy and data rights (2.6).
| Loaded inside the "web" middleware group by GovernanceServiceProvider.
| The guidelines, terms and privacy pages are app-shell routes.
*/

Route::middleware('auth')->group(function () {
    Route::get('/privacy/my-data', [PrivacyController::class, 'show'])->name('privacy.data');
    Route::post('/privacy/accept', [PrivacyController::class, 'accept'])->name('privacy.accept');
    Route::get('/privacy/my-data/download', [PrivacyController::class, 'export'])
        ->middleware('throttle:5,1,data-export')->name('privacy.export');
    Route::delete('/privacy/account', [PrivacyController::class, 'destroy'])->name('privacy.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/report/{type}/{id}', [ReportController::class, 'create'])->whereNumber('id')->name('reports.create');
    Route::post('/report/{type}/{id}', [ReportController::class, 'store'])->whereNumber('id')
        ->middleware('throttle:10,1,reports')->name('reports.store');

    Route::middleware('can:content.moderate')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/reports', [ModerationController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [ModerationController::class, 'show'])->name('reports.show');
        Route::put('/reports/{report}', [ModerationController::class, 'decide'])->name('reports.decide');
        Route::get('/moderation-log', [ModerationController::class, 'log'])->name('moderation.log');
    });
});
