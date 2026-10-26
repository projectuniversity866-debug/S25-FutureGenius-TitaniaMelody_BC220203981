<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Organization (employer) profiles (roadmap 1.12). An opportunity can be
 * published under one of its poster's organizations; `company` stays as the
 * display name so posts without an organization keep working.
 *
 * Existing posts get an organization for each poster + company name, so no
 * opportunity is left without a profile page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // owner
            $table->string('name', 120);
            $table->string('slug')->unique();
            $table->string('tagline', 160)->nullable();
            $table->text('about')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('industry', 80)->nullable();
            $table->string('size', 16)->nullable();
            $table->timestamps();
        });

        Schema::table('job_posts', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
        });

        $pairs = DB::table('job_posts')->select('user_id', 'company')->distinct()->get();
        foreach ($pairs as $pair) {
            $base = Str::slug($pair->company) ?: 'organization';
            $slug = $base;
            for ($i = 2; DB::table('organizations')->where('slug', $slug)->exists(); $i++) {
                $slug = $base.'-'.$i;
            }

            $id = DB::table('organizations')->insertGetId([
                'user_id' => $pair->user_id, 'name' => Str::limit($pair->company, 117), 'slug' => $slug,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('job_posts')->where('user_id', $pair->user_id)->where('company', $pair->company)
                ->update(['organization_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::dropIfExists('organizations');
    }
};
