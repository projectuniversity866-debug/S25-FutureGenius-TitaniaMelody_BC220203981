<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Knowledge Hub (roadmap 2.2): articles and step-by-step guides written by
 * verified experts, filed under the same four topic tracks as Q&A.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 16)->default('article');   // article | guide
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 300);
            $table->longText('body');                          // Markdown
            $table->unsignedSmallInteger('reading_minutes')->default(1);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();     // null = draft
            $table->timestamps();

            $table->index(['published_at', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
