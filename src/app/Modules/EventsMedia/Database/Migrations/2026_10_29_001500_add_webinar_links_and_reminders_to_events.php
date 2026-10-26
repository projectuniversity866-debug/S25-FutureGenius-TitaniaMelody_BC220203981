<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR4 "Webinar integration" (roadmap 3.8): the joining link of an online or
 * hybrid event (Zoom, Google Meet or Jitsi), shown only to people with a
 * seat; and "Automated emails" (3.9): when each reminder went out, so the
 * scheduled command never sends one twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('meeting_url', 500)->nullable()->after('venue');
            $table->string('meeting_notes', 255)->nullable()->after('meeting_url'); // passcode, dial-in…
            $table->timestamp('organizer_reminded_at')->nullable()->after('cancel_reason');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->timestamp('reminded_day_at')->nullable()->after('cancelled_at');  // "tomorrow" reminder
            $table->timestamp('reminded_hour_at')->nullable()->after('reminded_day_at'); // "starts in an hour"
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['reminded_day_at', 'reminded_hour_at']);
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['meeting_url', 'meeting_notes', 'organizer_reminded_at']);
        });
    }
};
