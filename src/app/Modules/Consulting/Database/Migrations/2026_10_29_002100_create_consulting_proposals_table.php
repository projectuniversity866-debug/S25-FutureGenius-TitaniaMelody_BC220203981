<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR6 "Proposal tracking" (roadmap 3.14): a consultant's offer to one of
 * their clients, Draft → Sent → Accepted / Rejected → Completed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consulting_client_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('body');                               // Markdown: scope, deliverables, timeline
            $table->unsignedInteger('amount_pkr')->nullable();  // null = free
            $table->date('valid_until')->nullable();
            $table->string('status', 16)->default('draft');     // draft | sent | accepted | rejected | completed
            $table->string('response_note', 500)->nullable();   // the client's reason, given with the decision
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['consultant_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulting_proposals');
    }
};
