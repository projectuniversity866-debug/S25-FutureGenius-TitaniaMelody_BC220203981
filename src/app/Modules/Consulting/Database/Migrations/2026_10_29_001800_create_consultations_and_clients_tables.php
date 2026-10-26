<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR6 "Consultation booking" (roadmap 3.12): booked sessions. A seat in a
 * consultant's calendar can be taken once: `slot_key` is set while a booking
 * is active and is unique, so the database itself refuses a double booking
 * even if two requests arrive at the same moment.
 *
 * FR6 "Client CRM" (3.13): each consultant's own client list — members who
 * booked, or people added by hand — with private, dated notes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulting_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // a member, or null for someone outside AQDesk
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('organization')->nullable();
            $table->string('status', 16)->default('active');   // lead | active | past
            $table->string('source', 16)->default('manual');   // booking | manual
            $table->timestamps();

            $table->unique(['consultant_profile_id', 'user_id']);
            $table->index(['consultant_profile_id', 'status']);
        });

        Schema::create('consulting_client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulting_client_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('consulting_client_id')->nullable()->constrained()->nullOnDelete();   // the CRM record (3.13)
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('topic', 150);
            $table->text('message')->nullable();               // what the client wants help with
            $table->string('meeting_url', 500)->nullable();    // a Jitsi room made for this session
            $table->string('status', 16)->default('confirmed'); // confirmed | completed | cancelled
            $table->string('slot_key', 64)->nullable()->unique(); // "profile:start" while active; null once cancelled
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['consultant_profile_id', 'status', 'starts_at']);
            $table->index(['client_id', 'status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('consulting_client_notes');
        Schema::dropIfExists('consulting_clients');
    }
};
