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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->nullable(); // estimated completion time
            $table->boolean('is_published')->default(false);
            $table->boolean('is_premium')->default(false); // for future premium-only modules
            $table->timestamps();
            $table->softDeletes(); // archive instead of delete

            $table->index('order');
            $table->index('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
