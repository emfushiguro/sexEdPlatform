<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_speakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('role')->default('speaker');
            $table->timestamps();

            $table->unique(['seminar_id', 'user_id']);
            $table->index('seminar_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_speakers');
    }
};
