<?php

use App\Modules\Communications\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Modules\Communications\Http\Controllers\Admin\CampaignController;
use App\Modules\Communications\Http\Controllers\AnnouncementController;
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
Route::get('/newsletter/open/{token}', [NewsletterController::class, 'open'])->where('token', '[A-Za-z0-9]{40}')->name('newsletter.open');   // 3.17

// Announcements (3.18).
Route::get('/updates', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::post('/updates/{announcement}/dismiss', [AnnouncementController::class, 'dismiss'])->name('announcements.dismiss');

Route::middleware(['auth', 'verified', 'can:announcements.manage'])->prefix('admin/announcements')->name('admin.announcements.')->group(function () {
    Route::get('/', [AdminAnnouncementController::class, 'index'])->name('index');
    Route::get('/new', [AdminAnnouncementController::class, 'create'])->name('create');
    Route::post('/', [AdminAnnouncementController::class, 'store'])->name('store');
    Route::get('/{announcement}/edit', [AdminAnnouncementController::class, 'edit'])->name('edit');
    Route::put('/{announcement}', [AdminAnnouncementController::class, 'update'])->name('update');
    Route::delete('/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('destroy');
});

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
    Route::get('/{campaign}/report', [CampaignController::class, 'report'])->name('report');   // 3.17
    Route::put('/{campaign}/retry', [CampaignController::class, 'retry'])->name('retry');
});
