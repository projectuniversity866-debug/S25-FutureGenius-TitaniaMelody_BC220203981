<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Polymorphic votes: the same table records upvotes on BOTH questions and
 * answers. "Upvote answers" is required by FR2; questions are included
 * because it costs nothing extra with a polymorphic relation.
 *
 * value = +1 (upvote) or -1 (downvote)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('votable');           // votable_id + votable_type
            $table->tinyInteger('value');        // 1 or -1
            $table->timestamps();

            // One vote per user per item.
            $table->unique(['user_id', 'votable_id', 'votable_type'], 'votes_unique_per_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
