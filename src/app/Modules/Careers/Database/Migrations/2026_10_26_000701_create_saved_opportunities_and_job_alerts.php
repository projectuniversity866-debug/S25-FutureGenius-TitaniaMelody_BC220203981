<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Career tools (roadmap 1.15): saved opportunities, job alerts, and a
 * record of when the scheduler closed an opportunity at its deadline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'job_post_id']);
        });

        Schema::create('job_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('keywords', 120)->nullable();
            $table->string('type', 32)->nullable();
            $table->string('work_mode', 16)->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::table('job_posts', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('is_active'); // set by careers:close-expired
        });
    }

    public function down(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
        Schema::dropIfExists('job_alerts');
        Schema::dropIfExists('saved_opportunities');
    }
};
