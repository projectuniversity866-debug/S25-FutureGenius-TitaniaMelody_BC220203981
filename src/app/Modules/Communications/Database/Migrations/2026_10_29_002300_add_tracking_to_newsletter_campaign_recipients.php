<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR7 "Campaign reports" (roadmap 3.17): each recipient's email carries a
 * tiny image with its own unguessable token, so the report can count who
 * opened it (first time and how often).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->string('token', 40)->nullable()->unique();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'opened_at', 'open_count']);
        });
    }
};
