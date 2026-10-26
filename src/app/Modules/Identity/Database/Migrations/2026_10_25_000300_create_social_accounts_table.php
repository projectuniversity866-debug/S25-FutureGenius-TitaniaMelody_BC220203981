<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google / GitHub identities linked to an AQDesk account (FR1 social login).
 * One user may link several providers; each provider identity belongs to one
 * user. OAuth tokens are not stored — AQDesk only needs to know who signed in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);          // google | github
            $table->string('provider_id');           // the provider's user id
            $table->string('email')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
