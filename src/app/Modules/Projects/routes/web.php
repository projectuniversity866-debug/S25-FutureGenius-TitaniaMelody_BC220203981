<?php

use App\Modules\Projects\Http\Controllers\InvitationController;
use App\Modules\Projects\Http\Controllers\ProjectController;
use App\Modules\Projects\Http\Controllers\ProjectFileController;
use App\Modules\Projects\Http\Controllers\ProjectMemberController;
use Illuminate\Support\Facades\Route;

/*
| Projects — FR3 Project Collaboration. Loaded inside the "web" middleware
| group by ProjectsServiceProvider.
*/

// The emailed invitation link (3.2): viewable and declinable without an
// account; accepting needs one with the invited email address.
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/decline', [InvitationController::class, 'decline'])->middleware('throttle:10,1,invitations')->name('invitations.decline');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])->middleware(['auth', 'verified', 'throttle:10,1,invitations'])->name('invitations.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/new', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::put('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::put('/projects/{project}/members/{member}', [ProjectMemberController::class, 'update'])->name('projects.members.update');
    Route::delete('/projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    Route::delete('/projects/{project}/leave', [ProjectMemberController::class, 'leave'])->name('projects.leave');

    Route::post('/projects/{project}/invitations', [InvitationController::class, 'store'])->middleware('throttle:20,1,invitations')->name('projects.invitations.store');
    Route::put('/projects/{project}/invitations/{invitation}', [InvitationController::class, 'resend'])->name('projects.invitations.resend');
    Route::delete('/projects/{project}/invitations/{invitation}', [InvitationController::class, 'cancel'])->name('projects.invitations.cancel');

    Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])->name('projects.files.store');
    Route::get('/projects/{project}/files/{file}', [ProjectFileController::class, 'show'])->name('projects.files.show');
    Route::post('/projects/{project}/files/{file}/versions', [ProjectFileController::class, 'storeVersion'])->name('projects.files.versions.store');
    Route::get('/projects/{project}/files/{file}/versions/{version}', [ProjectFileController::class, 'download'])->name('projects.files.download');
    Route::delete('/projects/{project}/files/{file}', [ProjectFileController::class, 'destroy'])->name('projects.files.destroy');

    // Declared last so /projects/new wins over the {project} wildcard.
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
});
