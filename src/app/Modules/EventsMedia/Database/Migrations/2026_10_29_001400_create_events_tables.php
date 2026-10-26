<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FR4 "Event creation" (roadmap 3.6): events with a capacity and a
 * waitlist; and "Event registration" (3.7): an optional Form Builder form
 * whose questions every registrant answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();     // organizer
            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();     // registration questions (3.7)
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 255);
            $table->text('description')->nullable();
            $table->string('format', 16);                     // in_person | online | hybrid
            $table->string('venue')->nullable();
            $table->timestamp('starts_at');                   // stored in UTC
            $table->timestamp('ends_at');
            $table->string('timezone', 64)->default('Asia/Karachi'); // shown to attendees in this zone
            $table->unsignedInteger('capacity')->nullable();  // null = unlimited
            $table->timestamp('registration_closes_at')->nullable();
            $table->string('status', 16)->default('draft');   // draft | published | cancelled
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_response_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16);                     // confirmed | waitlisted | cancelled
            $table->timestamp('queued_at');                   // waitlist order: first come, first served
            $table->timestamp('promoted_at')->nullable();     // moved from the waitlist to a seat
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'status', 'queued_at']);
        });

        // Existing databases: the new "Organize events" permission for admins and experts.
        if (Schema::hasTable('permissions') && ! DB::table('permissions')->where('slug', 'events.create')->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'slug' => 'events.create', 'name' => 'Organize events and see their attendees', 'group' => 'Events',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('roles')->whereIn('slug', ['admin', 'expert'])->pluck('id') as $role) {
                DB::table('permission_role')->insert(['permission_id' => $id, 'role_id' => $role]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
        DB::table('permissions')->where('slug', 'events.create')->delete();
    }
};
