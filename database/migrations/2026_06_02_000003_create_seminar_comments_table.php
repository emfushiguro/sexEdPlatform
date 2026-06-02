<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('status')->default('visible');
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('hidden_at')->nullable();
            $table->text('hidden_reason')->nullable();
            $table->timestamps();

            $table->index(['seminar_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_comments');
    }
};
