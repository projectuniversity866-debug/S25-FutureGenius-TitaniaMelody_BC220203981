<?php

use App\Modules\Consulting\Http\Controllers\BookingController;
use App\Modules\Consulting\Http\Controllers\ClientController;
use App\Modules\Consulting\Http\Controllers\ConsultantController;
use App\Modules\Consulting\Http\Controllers\MyConsultingController;
use App\Modules\Consulting\Http\Controllers\ProposalController;
use App\Modules\Consulting\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

/*
| Consulting — FR6 Consulting Management. Loaded inside the "web" middleware
| group by ConsultingServiceProvider.
*/

Route::get('/consultants', [ConsultantController::class, 'index'])->name('consultants.index');
Route::get('/consultants/{consultant}', [ConsultantController::class, 'show'])->name('consultants.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/consultants/{consultant}/book', [BookingController::class, 'create'])->name('consultants.book');
    Route::post('/consultants/{consultant}/book', [BookingController::class, 'store'])->middleware('throttle:10,1,consulting-booking')->name('consultants.book.store');
});

Route::middleware(['auth', 'verified'])->prefix('consulting')->name('consulting.')->group(function () {
    // Sessions (3.12).
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{consultation}', [SessionController::class, 'show'])->name('sessions.show');
    Route::put('/sessions/{consultation}/cancel', [SessionController::class, 'cancel'])->name('sessions.cancel');
    Route::put('/sessions/{consultation}/complete', [SessionController::class, 'complete'])->name('sessions.complete');

    // Clients (3.13).
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/new', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    Route::post('/clients/{client}/notes', [ClientController::class, 'storeNote'])->name('clients.notes.store');
    Route::delete('/clients/{client}/notes/{note}', [ClientController::class, 'destroyNote'])->name('clients.notes.destroy');

    // Proposals (3.14).
    Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/clients/{client}/proposals/new', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/clients/{client}/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
    Route::get('/proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
    Route::put('/proposals/{proposal}', [ProposalController::class, 'update'])->name('proposals.update');
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
    Route::put('/proposals/{proposal}/send', [ProposalController::class, 'send'])->name('proposals.send');
    Route::put('/proposals/{proposal}/decide', [ProposalController::class, 'decide'])->name('proposals.decide');
    Route::put('/proposals/{proposal}/complete', [ProposalController::class, 'complete'])->name('proposals.complete');

    Route::get('/profile', [MyConsultingController::class, 'edit'])->name('profile');
    Route::put('/profile', [MyConsultingController::class, 'update'])->name('profile.update');
    Route::get('/availability', [MyConsultingController::class, 'availability'])->name('availability');
    Route::post('/availability/hours', [MyConsultingController::class, 'storeHours'])->name('hours.store');
    Route::delete('/availability/hours/{rule}', [MyConsultingController::class, 'destroyHours'])->name('hours.destroy');
    Route::post('/availability/days-off', [MyConsultingController::class, 'storeDayOff'])->name('days-off.store');
    Route::delete('/availability/days-off/{day}', [MyConsultingController::class, 'destroyDayOff'])->name('days-off.destroy');
});
