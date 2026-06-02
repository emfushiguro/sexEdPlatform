<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->unsignedInteger('total_seconds')->default(0);
            $table->string('status')->default('registered');
            $table->timestamps();

            $table->unique(['seminar_id', 'user_id']);
            $table->index(['seminar_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_attendances');
    }
};
