<?php

use App\Modules\Community\Http\Controllers\Admin\QuestionModerationController;
use App\Modules\Community\Http\Controllers\AnswerController;
use App\Modules\Community\Http\Controllers\QuestionController;
use App\Modules\Community\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

/*
| Community — FR2 Q&A. Loaded inside the "web" middleware group by
| CommunityServiceProvider.
*/

// Browsable without an account; posting, answering and voting need one.
Route::get('/questions', [QuestionController::class, 'index'])->name('questions.index');

// Posting needs a confirmed email address (FR1 verification; limits spam).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/questions/create', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

    Route::post('/questions/{question}/answers', [AnswerController::class, 'store'])->name('answers.store');
    Route::put('/answers/{answer}', [AnswerController::class, 'update'])->name('answers.update');
    Route::delete('/answers/{answer}', [AnswerController::class, 'destroy'])->name('answers.destroy');
    Route::post('/answers/{answer}/accept', [AnswerController::class, 'accept'])->name('answers.accept');

    Route::post('/questions/{question}/vote', [VoteController::class, 'question'])->name('votes.question');
    Route::post('/answers/{answer}/vote', [VoteController::class, 'answer'])->name('votes.answer');

    Route::middleware('can:content.moderate')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/questions', [QuestionModerationController::class, 'index'])->name('questions.index');
        Route::put('/questions/{question}/toggle', [QuestionModerationController::class, 'toggle'])->name('questions.toggle');
        Route::delete('/questions/{question}', [QuestionModerationController::class, 'destroy'])->name('questions.destroy');
    });
});

// Declared last so /questions/create is matched before the {question} wildcard.
Route::get('/questions/{question}', [QuestionController::class, 'show'])->name('questions.show');
