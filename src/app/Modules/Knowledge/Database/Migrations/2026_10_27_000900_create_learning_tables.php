<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Learning Hub (roadmap 2.3, EdTech): a library of learning resources,
 * learning paths that order them into steps, and each member's progress.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();                 // curator
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('kind', 16);                       // video | course | tutorial | article | book | tool
            $table->string('level', 16)->default('beginner'); // beginner | intermediate | advanced
            $table->string('url');
            $table->string('provider', 80)->nullable();
            $table->text('description');
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->boolean('is_free')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'kind']);
        });

        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 300);
            $table->string('level', 16)->default('beginner');
            $table->timestamp('published_at')->nullable();   // null = draft
            $table->timestamps();
        });

        Schema::create('learning_path_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_resource_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('note', 300)->nullable();          // why this step
            $table->timestamps();

            $table->unique(['learning_path_id', 'learning_resource_id']);
        });

        Schema::create('learning_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_path_step_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->useCurrent();

            $table->unique(['user_id', 'learning_path_step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_progress');
        Schema::dropIfExists('learning_path_steps');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('learning_resources');
    }
};
