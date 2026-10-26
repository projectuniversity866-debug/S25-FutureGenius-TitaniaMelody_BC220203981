<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR3 "Provide feedback" (roadmap 3.4): discussion threads inside a project,
 * optionally about one shared file, that can be marked resolved; and
 * "Project tracking" (3.5): a task board with To do / Doing / Done.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_file_id')->nullable()->constrained()->nullOnDelete(); // feedback on a file
            $table->string('title');
            $table->text('body');
            $table->string('status', 16)->default('open');    // open | resolved
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('replies_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('project_thread_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 16)->default('todo');     // todo | doing | done
            $table->string('priority', 16)->default('normal'); // low | normal | high
            $table->date('due_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_thread_replies');
        Schema::dropIfExists('project_threads');
    }
};
