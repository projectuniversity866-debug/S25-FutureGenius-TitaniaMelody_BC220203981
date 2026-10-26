<?php

use App\Modules\Consulting\Http\Controllers\ConsultantController;
use App\Modules\Consulting\Http\Controllers\MyConsultingController;
use Illuminate\Support\Facades\Route;

/*
| Consulting — FR6 Consulting Management. Loaded inside the "web" middleware
| group by ConsultingServiceProvider.
*/

Route::get('/consultants', [ConsultantController::class, 'index'])->name('consultants.index');
Route::get('/consultants/{consultant}', [ConsultantController::class, 'show'])->name('consultants.show');

Route::middleware(['auth', 'verified'])->prefix('consulting')->name('consulting.')->group(function () {
    Route::get('/profile', [MyConsultingController::class, 'edit'])->name('profile');
    Route::put('/profile', [MyConsultingController::class, 'update'])->name('profile.update');
    Route::get('/availability', [MyConsultingController::class, 'availability'])->name('availability');
    Route::post('/availability/hours', [MyConsultingController::class, 'storeHours'])->name('hours.store');
    Route::delete('/availability/hours/{rule}', [MyConsultingController::class, 'destroyHours'])->name('hours.destroy');
    Route::post('/availability/days-off', [MyConsultingController::class, 'storeDayOff'])->name('days-off.store');
    Route::delete('/availability/days-off/{day}', [MyConsultingController::class, 'destroyDayOff'])->name('days-off.destroy');
});
