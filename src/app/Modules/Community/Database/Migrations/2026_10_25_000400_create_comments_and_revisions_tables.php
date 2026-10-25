<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roadmap 1.8: short comments on questions and answers, and the edit
 * history of both (each row is the version *before* an edit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable');                 // question | answer
            $table->string('body', 600);
            $table->timestamps();
        });

        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisable');                   // question | answer
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who made the edit
            $table->string('title')->nullable();           // questions only
            $table->text('body');
            $table->timestamp('created_at')->useCurrent(); // when it was replaced
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
        Schema::dropIfExists('comments');
    }
};
