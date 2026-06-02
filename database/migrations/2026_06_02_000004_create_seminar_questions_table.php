<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->string('status')->default('pending');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('answered_at')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('hidden_at')->nullable();
            $table->text('hidden_reason')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['seminar_id', 'status']);
            $table->index('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_questions');
    }
};
