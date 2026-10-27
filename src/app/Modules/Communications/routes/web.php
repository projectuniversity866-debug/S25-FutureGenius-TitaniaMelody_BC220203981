<?php

use App\Modules\Communications\Http\Controllers\Admin\CampaignController;
use App\Modules\Communications\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;

/*
| Communications — FR7 Newsletter and Announcements. Loaded inside the "web"
| middleware group by CommunicationsServiceProvider.
*/

// Subscription (3.15).
Route::get('/newsletter', [NewsletterController::class, 'show'])->name('newsletter.show');
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,1,newsletter')->name('newsletter.subscribe');
Route::get('/newsletter/confirm/{subscriber:token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{subscriber:token}', [NewsletterController::class, 'confirmUnsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{subscriber:token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe.store');

// Campaigns (3.16).
Route::middleware(['auth', 'verified', 'can:newsletter.send'])->prefix('admin/newsletter')->name('admin.newsletter.')->group(function () {
    Route::get('/', [CampaignController::class, 'index'])->name('index');
    Route::get('/new', [CampaignController::class, 'create'])->name('create');
    Route::post('/', [CampaignController::class, 'store'])->name('store');
    Route::get('/{campaign}', [CampaignController::class, 'show'])->name('show');
    Route::get('/{campaign}/edit', [CampaignController::class, 'edit'])->name('edit');
    Route::put('/{campaign}', [CampaignController::class, 'update'])->name('update');
    Route::delete('/{campaign}', [CampaignController::class, 'destroy'])->name('destroy');
    Route::post('/{campaign}/test', [CampaignController::class, 'test'])->middleware('throttle:5,1,newsletter-test')->name('test');
    Route::put('/{campaign}/schedule', [CampaignController::class, 'schedule'])->name('schedule');
    Route::put('/{campaign}/unschedule', [CampaignController::class, 'unschedule'])->name('unschedule');
    Route::put('/{campaign}/send', [CampaignController::class, 'send'])->name('send');
});
