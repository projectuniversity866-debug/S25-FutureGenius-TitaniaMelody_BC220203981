<?php

use App\Modules\Platform\Http\Controllers\ActivityLogController;
use App\Modules\Platform\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
| Platform — shared services. Loaded inside the "web" middleware group by
| PlatformServiceProvider.
*/

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::middleware('can:activity.view')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });
});
