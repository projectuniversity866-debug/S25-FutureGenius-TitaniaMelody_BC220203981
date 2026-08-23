<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extended profile data, kept separate from `users` so the auth table stays lean.
 * Supports FR1 "Profile management" / prototype module #4 "User Profile".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('avatar_path')->nullable();
            $table->string('headline')->nullable();       // "Final year CS student"
            $table->text('bio')->nullable();
            $table->string('skills')->nullable();         // comma separated, e.g. "PHP, Laravel"
            $table->string('organization')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('github')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
