<?php

use App\Modules\EventsMedia\Http\Controllers\EventController;
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

// Declared last so /events/new wins over the {event} wildcard.
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
