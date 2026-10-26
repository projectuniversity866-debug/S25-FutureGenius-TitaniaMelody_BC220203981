<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FR6 "Consultant setup" (roadmap 3.11): a consultant's public profile,
 * weekly working hours in their own time zone, and days off. Bookable slots
 * are worked out from these (3.12 adds the bookings that take them).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('headline', 150);
            $table->text('bio')->nullable();
            $table->json('expertise')->nullable();               // up to 8 short topics
            $table->unsignedSmallInteger('session_minutes')->default(60);
            $table->unsignedInteger('price_pkr')->nullable();     // null = free
            $table->string('timezone', 64)->default('Asia/Karachi');
            $table->unsignedSmallInteger('notice_hours')->default(24);   // earliest booking from now
            $table->unsignedSmallInteger('window_days')->default(30);    // latest booking from now
            $table->boolean('is_accepting')->default(true);
            $table->timestamps();
        });

        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');               // ISO: 1 = Monday … 7 = Sunday
            $table->string('starts', 5);                          // "09:00", the consultant's local time
            $table->string('ends', 5);
            $table->timestamps();

            $table->index(['consultant_profile_id', 'weekday']);
        });

        Schema::create('availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_profile_id')->constrained()->cascadeOnDelete();
            $table->date('date');                                 // a day off, in the consultant's time zone
            $table->string('reason', 150)->nullable();            // private to the consultant
            $table->timestamps();

            $table->unique(['consultant_profile_id', 'date']);
        });

        // Existing databases: the new "Offer consulting" permission for admins and experts.
        if (Schema::hasTable('permissions') && ! DB::table('permissions')->where('slug', 'consulting.offer')->exists()) {
            $id = DB::table('permissions')->insertGetId([
                'slug' => 'consulting.offer', 'name' => 'Offer consulting sessions', 'group' => 'Consulting',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('roles')->whereIn('slug', ['admin', 'expert'])->pluck('id') as $role) {
                DB::table('permission_role')->insert(['permission_id' => $id, 'role_id' => $role]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_exceptions');
        Schema::dropIfExists('availability_rules');
        Schema::dropIfExists('consultant_profiles');
        DB::table('permissions')->where('slug', 'consulting.offer')->delete();
    }
};
