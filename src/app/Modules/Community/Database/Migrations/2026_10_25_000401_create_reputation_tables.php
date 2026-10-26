<?php

use App\Modules\Community\Services\ReputationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roadmap 1.9: reputation as a ledger of events (every point has a reason
 * and can be reversed when a vote is removed) and badges once earned.
 * Existing votes and accepted answers are replayed into the ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reputation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();            // who gains/loses
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); // who caused it
            $table->string('type', 32);                    // answer_upvoted, answer_accepted, …
            $table->integer('points');
            $table->nullableMorphs('subject');             // the vote or answer behind it
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge', 40);
            $table->timestamp('awarded_at')->useCurrent();

            $table->unique(['user_id', 'badge']);
        });

        app(ReputationService::class)->rebuild();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('reputation_events');
    }
};
