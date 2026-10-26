<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Digital governance (roadmap 2.5 – 2.6): content reports and the moderation
 * queue, a permanent decision log, and members' consent to the terms and
 * privacy policy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // reporter
            $table->morphs('reportable');
            $table->string('reason', 24);
            $table->text('details')->nullable();
            $table->string('status', 16)->default('open');                           // open | actioned | dismissed
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Kept even after the content is gone: what was decided, by whom, why.
        Schema::create('moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 32);
            $table->unsignedBigInteger('subject_id');
            $table->string('subject_label');
            $table->string('action', 16);          // removed | dismissed
            $table->string('reason', 24)->nullable();
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('reports_count')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('version', 16);         // LegalDocuments::VERSION
            $table->timestamp('accepted_at')->useCurrent();

            $table->unique(['user_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
        Schema::dropIfExists('moderation_actions');
        Schema::dropIfExists('reports');
    }
};
