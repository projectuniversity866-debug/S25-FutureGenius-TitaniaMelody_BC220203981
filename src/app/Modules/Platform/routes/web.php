<?php

use App\Modules\Platform\Http\Controllers\ActivityLogController;
use App\Modules\Platform\Http\Controllers\FormController;
use App\Modules\Platform\Http\Controllers\MarkdownPreviewController;
use App\Modules\Platform\Http\Controllers\NotificationController;
use App\Modules\Platform\Http\Controllers\PublicFormController;
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

    // Forms engine (2.4): building forms and reading their responses.
    Route::middleware('verified')->group(function () {
        Route::get('/forms', [FormController::class, 'index'])->name('forms.index');
        Route::get('/forms/new', [FormController::class, 'create'])->name('forms.create');
        Route::post('/forms', [FormController::class, 'store'])->name('forms.store');
        Route::get('/forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
        Route::put('/forms/{form}', [FormController::class, 'update'])->name('forms.update');
        Route::put('/forms/{form}/status', [FormController::class, 'status'])->name('forms.status');
        Route::delete('/forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
        Route::post('/forms/{form}/fields', [FormController::class, 'storeField'])->name('forms.fields.store');
        Route::put('/form-fields/{field}', [FormController::class, 'updateField'])->name('forms.fields.update');
        Route::put('/form-fields/{field}/move', [FormController::class, 'moveField'])->name('forms.fields.move');
        Route::delete('/form-fields/{field}', [FormController::class, 'destroyField'])->name('forms.fields.destroy');
        Route::get('/forms/{form}/responses', [FormController::class, 'responses'])->name('forms.responses');
        Route::get('/forms/{form}/responses.csv', [FormController::class, 'export'])->name('forms.export');
    });

    Route::post('/markdown/preview', MarkdownPreviewController::class)
        ->middleware('throttle:60,1')->name('markdown.preview');

    Route::middleware('can:activity.view')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    });
});

// Public form links (2.4): anyone may fill in an open form, unless it
// requires sign-in. Submissions are throttled per visitor.
Route::get('/f/{form}', [PublicFormController::class, 'show'])->name('forms.fill');
Route::post('/f/{form}', [PublicFormController::class, 'submit'])->middleware('throttle:20,1,forms')->name('forms.submit');
Route::get('/f/{form}/thanks', [PublicFormController::class, 'thanks'])->name('forms.thanks');
