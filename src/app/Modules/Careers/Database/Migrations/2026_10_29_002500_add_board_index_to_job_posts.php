<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roadmap 4.2: the opportunity board lists open posts newest first, and the
 * daily deadline closing looks for open posts past their deadline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'job_posts_board_index');
            $table->index(['is_active', 'deadline'], 'job_posts_deadline_index');
        });
    }

    public function down(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropIndex('job_posts_board_index');
            $table->dropIndex('job_posts_deadline_index');
        });
    }
};
