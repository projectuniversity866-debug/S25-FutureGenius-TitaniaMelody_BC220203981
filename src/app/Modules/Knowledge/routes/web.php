<?php

use App\Modules\Knowledge\Http\Controllers\Admin\VerificationReviewController;
use App\Modules\Knowledge\Http\Controllers\ArticleController;
use App\Modules\Knowledge\Http\Controllers\LearningController;
use App\Modules\Knowledge\Http\Controllers\LearningCurationController;
use App\Modules\Knowledge\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
| Knowledge — expert verification (2.1) and the Knowledge Hub (2.2). Loaded
| inside the "web" middleware group by KnowledgeServiceProvider.
*/

Route::get('/knowledge', [ArticleController::class, 'index'])->name('articles.index');

// Learning Hub (2.3): browsable by everyone; progress and curation need an account.
Route::get('/learn', [LearningController::class, 'index'])->name('learning.index');
Route::get('/learn/resources', [LearningController::class, 'resources'])->name('learning.resources');
Route::middleware('auth')->group(function () {
    Route::get('/learn/mine', [LearningController::class, 'mine'])->name('learning.mine');
    Route::post('/learn/steps/{step}/toggle', [LearningController::class, 'toggle'])->middleware('throttle:60,1,learning')->name('learning.steps.toggle');
});
Route::middleware(['auth', 'verified'])->prefix('learn/manage')->name('learning.')->group(function () {
    Route::get('/resources/new', [LearningCurationController::class, 'createResource'])->name('resources.create');
    Route::post('/resources', [LearningCurationController::class, 'storeResource'])->name('resources.store');
    Route::get('/resources/{resource}/edit', [LearningCurationController::class, 'editResource'])->name('resources.edit');
    Route::put('/resources/{resource}', [LearningCurationController::class, 'updateResource'])->name('resources.update');
    Route::delete('/resources/{resource}', [LearningCurationController::class, 'destroyResource'])->name('resources.destroy');

    Route::get('/paths/new', [LearningCurationController::class, 'createPath'])->name('paths.create');
    Route::post('/paths', [LearningCurationController::class, 'storePath'])->name('paths.store');
    Route::get('/paths/{path}/edit', [LearningCurationController::class, 'editPath'])->name('paths.edit');
    Route::put('/paths/{path}', [LearningCurationController::class, 'updatePath'])->name('paths.update');
    Route::delete('/paths/{path}', [LearningCurationController::class, 'destroyPath'])->name('paths.destroy');

    Route::post('/paths/{path}/steps', [LearningCurationController::class, 'storeStep'])->name('steps.store');
    Route::put('/steps/{step}/move', [LearningCurationController::class, 'moveStep'])->name('steps.move');
    Route::delete('/steps/{step}', [LearningCurationController::class, 'destroyStep'])->name('steps.destroy');
});
Route::get('/learn/paths/{path}', [LearningController::class, 'show'])->name('learning.paths.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/expert-verification', [VerificationController::class, 'show'])->name('verification.show');
    Route::post('/expert-verification', [VerificationController::class, 'store'])->name('verification.store');
    Route::get('/expert-verification/{verification}/document', [VerificationController::class, 'document'])->name('verification.document');

    Route::get('/knowledge/mine', [ArticleController::class, 'mine'])->name('articles.mine');
    Route::get('/knowledge/write', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/knowledge', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/knowledge/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/knowledge/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/knowledge/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');

    Route::middleware('can:experts.verify')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/verifications', [VerificationReviewController::class, 'index'])->name('verifications.index');
        Route::get('/verifications/{verification}', [VerificationReviewController::class, 'show'])->name('verifications.show');
        Route::put('/verifications/{verification}', [VerificationReviewController::class, 'decide'])->name('verifications.decide');
    });
});

// Declared last so /knowledge/mine and /knowledge/write win over the {article} wildcard.
Route::get('/knowledge/{article}', [ArticleController::class, 'show'])->name('articles.show');
