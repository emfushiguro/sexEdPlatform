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
        Schema::create('module_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 50); // pdf, docx, mp4, etc.
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->boolean('is_premium')->default(true); // only premium users can download
            $table->timestamps();

            $table->index('module_id');
            $table->index('is_premium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_attachments');
    }
};
