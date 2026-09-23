<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_paths', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('thumbnail')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('learning_path_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();
            $table->unique(['learning_path_id', 'module_id']);
            $table->unique(['learning_path_id', 'position']);
            $table->index(['module_id', 'learning_path_id']);
        });

        Schema::create('learning_path_learner_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
            $table->string('category', 16);
            $table->timestamps();
            $table->unique(['learning_path_id', 'category']);
            $table->index(['category', 'learning_path_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_path_learner_categories');
        Schema::dropIfExists('learning_path_modules');
        Schema::dropIfExists('learning_paths');
    }
};
