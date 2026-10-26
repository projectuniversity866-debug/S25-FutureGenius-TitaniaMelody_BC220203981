<?php

use App\Modules\EventsMedia\Http\Controllers\EpisodeController;
use App\Modules\EventsMedia\Http\Controllers\EventController;
use App\Modules\EventsMedia\Http\Controllers\PodcastController;
use App\Modules\EventsMedia\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

/*
| EventsMedia — FR4 Events and Podcasts. Loaded inside the "web" middleware
| group by EventsMediaServiceProvider.
*/

Route::get('/events', [EventController::class, 'index'])->name('events.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/events/new', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::put('/events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
    Route::put('/events/{event}/cancel', [EventController::class, 'cancel'])->name('events.cancel');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

    Route::get('/events/{event}/register', [RegistrationController::class, 'create'])->name('events.register');
    Route::post('/events/{event}/register', [RegistrationController::class, 'store'])->middleware('throttle:10,1,event-registration')->name('events.register.store');
    Route::delete('/events/{event}/register', [RegistrationController::class, 'destroy'])->name('events.register.destroy');

    Route::get('/events/{event}/attendees', [RegistrationController::class, 'index'])->name('events.attendees');
    Route::get('/events/{event}/attendees.csv', [RegistrationController::class, 'export'])->name('events.attendees.export');
    Route::delete('/events/{event}/attendees/{registration}', [RegistrationController::class, 'remove'])->name('events.attendees.remove');
});

Route::get('/events/{event}/calendar.ics', [EventController::class, 'calendar'])->name('events.calendar');

// Podcasts (3.10). Episode slugs are unique within their series.
Route::get('/podcasts', [PodcastController::class, 'index'])->name('podcasts.index');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/podcasts/new', [PodcastController::class, 'create'])->name('podcasts.create');
    Route::post('/podcasts', [PodcastController::class, 'store'])->name('podcasts.store');
    Route::get('/podcasts/{series}/edit', [PodcastController::class, 'edit'])->name('podcasts.edit');
    Route::put('/podcasts/{series}', [PodcastController::class, 'update'])->name('podcasts.update');
    Route::delete('/podcasts/{series}', [PodcastController::class, 'destroy'])->name('podcasts.destroy');

    Route::scopeBindings()->group(function () {
        Route::get('/podcasts/{series}/episodes/new', [EpisodeController::class, 'create'])->name('podcasts.episodes.create');
        Route::post('/podcasts/{series}/episodes', [EpisodeController::class, 'store'])->name('podcasts.episodes.store');
        Route::get('/podcasts/{series}/{episode}/edit', [EpisodeController::class, 'edit'])->name('podcasts.episodes.edit');
        Route::put('/podcasts/{series}/{episode}', [EpisodeController::class, 'update'])->name('podcasts.episodes.update');
        Route::put('/podcasts/{series}/{episode}/publish', [EpisodeController::class, 'publish'])->name('podcasts.episodes.publish');
        Route::delete('/podcasts/{series}/{episode}', [EpisodeController::class, 'destroy'])->name('podcasts.episodes.destroy');
    });
});
Route::get('/podcasts/{series}/feed.xml', [PodcastController::class, 'feed'])->name('podcasts.feed');
Route::get('/podcasts/{series}', [PodcastController::class, 'show'])->name('podcasts.show');
Route::scopeBindings()->group(function () {
    Route::get('/podcasts/{series}/{episode}/audio', [EpisodeController::class, 'audio'])->name('podcasts.audio');
    Route::get('/podcasts/{series}/{episode}', [EpisodeController::class, 'show'])->name('podcasts.episodes.show');
});

// Declared last so /events/new wins over the {event} wildcard.
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
