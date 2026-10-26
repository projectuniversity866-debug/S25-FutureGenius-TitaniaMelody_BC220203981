<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The models moved from App\Models into app/Modules, so votes now store the
 * short morph aliases from CommunityServiceProvider instead of class names.
 * Existing rows are converted so no vote is lost.
 */
return new class extends Migration
{
    private const ALIASES = [
        'App\\Models\\Question' => 'question',
        'App\\Models\\Answer'   => 'answer',
    ];

    public function up(): void
    {
        foreach (self::ALIASES as $class => $alias) {
            DB::table('votes')->where('votable_type', $class)->update(['votable_type' => $alias]);
        }
    }

    public function down(): void
    {
        foreach (self::ALIASES as $class => $alias) {
            DB::table('votes')->where('votable_type', $alias)->update(['votable_type' => $class]);
        }
    }
};
