<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discovery (roadmap 1.10): members follow tags, and save questions to read
 * later. Bookmarks are polymorphic, like votes and comments, so other kinds
 * of post can be saved later without a new table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tag_id', 'user_id']);
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('bookmarkable');
            $table->timestamps();

            // One bookmark per user per item.
            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('tag_follows');
    }
};
