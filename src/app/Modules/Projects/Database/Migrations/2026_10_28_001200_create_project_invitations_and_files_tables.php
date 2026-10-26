<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR3 "Invite collaborators" (roadmap 3.2): emailed invitations with a
 * secret token that expires; and "Share files" (3.3): project files whose
 * every upload is kept as a numbered version, stored privately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('role', 16);
            $table->string('token_hash', 64)->unique();      // sha256 of the token in the link
            $table->string('status', 16)->default('pending'); // pending | accepted | declined | cancelled
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who added it
            $table->string('name');
            $table->unsignedSmallInteger('versions_count')->default(0);
            $table->timestamps();
        });

        Schema::create('project_file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // uploader
            $table->unsignedSmallInteger('number');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size');
            $table->string('note', 255)->nullable();          // what changed
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['project_file_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_file_versions');
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('project_invitations');
    }
};
