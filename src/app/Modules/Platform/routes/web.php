<?php

use App\Modules\Platform\Http\Controllers\ActivityLogController;
use App\Modules\Platform\Http\Controllers\MarkdownPreviewController;
use App\Modules\Platform\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
| Platform — shared services. Loaded inside the "web" middleware group by
| PlatformServiceProvider.
*/

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    // Polled by the bell every 30 seconds while the tab is in use.
    Route::get('/notifications/summary', [NotificationController::class, 'summary'])
        ->middleware('throttle:30,1,bell')->name('notifications.summary');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])
        ->whereUuid('notification')->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::post('/markdown/preview', MarkdownPreviewController::class)
        ->middleware('throttle:60,1')->name('markdown.preview');

    Route::middleware('can:activity.view')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });
});
