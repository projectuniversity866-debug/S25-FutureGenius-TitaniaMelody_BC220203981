<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Professional opportunities beyond jobs (roadmap 1.13): scholarships,
 * fellowships, grants, hackathons and calls for papers. The type list now
 * lives in JobPost::TYPES (validated in JobPostRequest), so the column
 * becomes a plain string instead of a fixed enum. Programme or event dates,
 * eligibility and a link to the organiser's page are optional for every type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->string('type', 32)->default('job')->change();
            $table->date('starts_on')->nullable()->after('deadline');
            $table->date('ends_on')->nullable()->after('starts_on');
            $table->text('eligibility')->nullable()->after('requirements');
            $table->string('external_url')->nullable()->after('ends_on');
        });
    }

    public function down(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropColumn(['starts_on', 'ends_on', 'eligibility', 'external_url']);
        });
    }
};
