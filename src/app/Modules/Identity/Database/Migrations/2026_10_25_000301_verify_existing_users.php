<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Email verification starts with this release. Accounts created before it
 * were never asked to verify, so they are treated as verified rather than
 * suddenly locked out of posting.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Not reversible: we cannot tell which accounts verified on their own.
    }
};
