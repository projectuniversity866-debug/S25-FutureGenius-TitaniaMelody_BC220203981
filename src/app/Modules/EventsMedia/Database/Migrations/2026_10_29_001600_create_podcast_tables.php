<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FR4 "Podcast publishing" (roadmap 3.10): series by a host, numbered
 * episodes with audio (uploaded or hosted elsewhere), show notes and a
 * transcript — required before an episode can be published (WCAG 1.2.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('podcast_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // host
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 255);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('podcast_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('podcast_series_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->string('slug');
            $table->string('summary', 255);
            $table->text('show_notes')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('audio_path')->nullable();          // uploaded file on the private disk, streamed
            $table->string('audio_mime', 64)->nullable();
            $table->unsignedBigInteger('audio_size')->nullable();
            $table->string('audio_url', 500)->nullable();      // or hosted elsewhere (https)
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status', 16)->default('draft');    // draft | published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['podcast_series_id', 'slug']);
            $table->unique(['podcast_series_id', 'number']);
            $table->index(['status', 'published_at']);
        });

        // Existing databases: the new "Publish podcasts" permission for admins and experts.
        if (Schema::hasTable('permissions') && ! DB::table('permissions')->where('slug', 'podcasts.publish')->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'slug' => 'podcasts.publish', 'name' => 'Publish podcast series and episodes', 'group' => 'Events',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('roles')->whereIn('slug', ['admin', 'expert'])->pluck('id') as $role) {
                DB::table('permission_role')->insert(['permission_id' => $id, 'role_id' => $role]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_episodes');
        Schema::dropIfExists('podcast_series');
        DB::table('permissions')->where('slug', 'podcasts.publish')->delete();
    }
};
