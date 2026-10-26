<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Expert verification (roadmap 2.1): a member applies with their field,
 * credentials and evidence; an administrator approves or rejects it. An
 * approved request is what the "Verified expert" badge shows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('field', 120);                      // area of expertise
            $table->text('credentials');                       // qualifications and experience
            $table->string('evidence_url')->nullable();        // LinkedIn, publications, portfolio
            $table->string('document_path')->nullable();       // private file (PrivateFiles)
            $table->string('status', 16)->default('pending');  // pending | approved | rejected
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();           // shown to the applicant
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Existing databases: add the new permission and give it to administrators
        // (fresh installs get it from RoleSeeder / PermissionCatalog).
        if (Schema::hasTable('permissions') && ! DB::table('permissions')->where('slug', 'experts.verify')->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'slug' => 'experts.verify', 'name' => 'Review expert verification requests', 'group' => 'Administration',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($admin = DB::table('roles')->where('slug', 'admin')->value('id')) {
                DB::table('permission_role')->insert(['permission_id' => $id, 'role_id' => $admin]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_verifications');
        DB::table('permissions')->where('slug', 'experts.verify')->delete();
    }
};
