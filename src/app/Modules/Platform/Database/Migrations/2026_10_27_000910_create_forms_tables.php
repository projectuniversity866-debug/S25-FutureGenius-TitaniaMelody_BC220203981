<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Forms engine (roadmap 2.4, Platform): creators build forms from fields,
 * respondents submit them. Shared by later modules — event registration
 * (3.7), consulting intake, job questions and surveys.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();   // creator
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 16)->default('draft');                    // draft | open | closed
            $table->boolean('requires_login')->default(false);
            $table->boolean('one_response_per_user')->default(false);
            $table->string('confirmation_message', 500)->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('label');
            $table->string('help', 300)->nullable();
            $table->json('options')->nullable();          // choices for choice fields
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });

        Schema::create('form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // null = anonymous visitor
            $table->timestamps();

            $table->index(['form_id', 'user_id']);
        });

        Schema::create('form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();            // JSON array for multiple choice
        });

        // Existing databases: the new "Build forms" permission for admins and experts.
        if (Schema::hasTable('permissions') && ! DB::table('permissions')->where('slug', 'forms.create')->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'slug' => 'forms.create', 'name' => 'Build forms and read their responses', 'group' => 'Platform',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('roles')->whereIn('slug', ['admin', 'expert'])->pluck('id') as $role) {
                DB::table('permission_role')->insert(['permission_id' => $id, 'role_id' => $role]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form_answers');
        Schema::dropIfExists('form_responses');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
        DB::table('permissions')->where('slug', 'forms.create')->delete();
    }
};
