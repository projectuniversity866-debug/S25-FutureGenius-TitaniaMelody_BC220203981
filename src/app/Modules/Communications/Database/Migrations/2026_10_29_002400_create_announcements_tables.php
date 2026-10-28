<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FR7 "Community updates" (roadmap 3.18): site announcements shown as a
 * banner on every page between their start and end, and who dismissed which.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 120);
            $table->string('body', 500);
            $table->string('link_url', 500)->nullable();
            $table->string('link_label', 60)->nullable();
            $table->string('tone', 16)->default('info');          // info | success | warning
            $table->string('audience', 16)->default('everyone');  // everyone | members | guests
            $table->boolean('is_dismissible')->default(true);
            $table->string('timezone', 64)->default('Asia/Karachi');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('announcement_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
        });

        // Existing databases: the new "Post site announcements" permission for admins.
        if (Schema::hasTable('permissions') && ! DB::table('permissions')->where('slug', 'announcements.manage')->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'slug' => 'announcements.manage', 'name' => 'Post site announcements and banners', 'group' => 'Communications',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('roles')->where('slug', 'admin')->pluck('id') as $role) {
                DB::table('permission_role')->insert(['permission_id' => $id, 'role_id' => $role]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_dismissals');
        Schema::dropIfExists('announcements');
        DB::table('permissions')->where('slug', 'announcements.manage')->delete();
    }
};
