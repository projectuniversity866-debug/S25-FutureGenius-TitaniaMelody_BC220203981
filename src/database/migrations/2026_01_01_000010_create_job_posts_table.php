<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR5 "Jobs and Opportunities Board" / prototype module #6.
 *
 * NOTE: named `job_posts`, not `jobs`, because Laravel already reserves the
 * `jobs` table for its queue worker (see 0001_01_01_000002_create_jobs_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // poster
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('company');
            $table->enum('type', ['job', 'internship'])->default('job');
            $table->enum('work_mode', ['onsite', 'remote', 'hybrid'])->default('onsite');
            $table->string('location')->nullable();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->date('deadline')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
