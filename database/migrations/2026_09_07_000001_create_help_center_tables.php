<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 500)->nullable();
            $table->string('icon_key', 50)->nullable();
            $table->json('audiences');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('help_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('help_category_id')->constrained('help_categories')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->string('summary', 500);
            $table->json('keywords')->nullable();
            $table->json('audiences');
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('help_article_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('help_article_id')->constrained('help_articles')->cascadeOnDelete();
            $table->string('heading', 180)->nullable();
            $table->longText('body');
            $table->string('image_path')->nullable();
            $table->string('image_alt_text', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['help_article_id', 'sort_order']);
        });

        Schema::create('help_article_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('help_article_id')->constrained('help_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_helpful');
            $table->timestamps();
            $table->unique(['help_article_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_article_votes');
        Schema::dropIfExists('help_article_sections');
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('help_categories');
    }
};
