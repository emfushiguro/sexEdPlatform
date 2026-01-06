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
        Schema::create('seminar_registrants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seminar_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['registered', 'attended', 'cancelled', 'no_show'])->default('registered');
            $table->timestamp('registered_at');
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();

            $table->unique(['seminar_id', 'user_id']); // prevent duplicate registrations
            $table->index('user_id');
            $table->index(['seminar_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seminar_registrants');
    }
};
