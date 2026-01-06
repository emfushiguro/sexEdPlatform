<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->nullable()->constrained()->onDelete('cascade'); // quiz after module
            $table->foreignId('lesson_id')->nullable()->constrained()->onDelete('cascade'); // quiz after lesson
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('passing_score')->default(70); // percentage to pass
            $table->integer('time_limit')->nullable(); // in seconds, null = no limit
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('module_id');
            $table->index('lesson_id');
            // Either module_id or lesson_id must be set, not both
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
