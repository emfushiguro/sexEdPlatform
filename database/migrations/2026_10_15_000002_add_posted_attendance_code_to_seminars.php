<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->text('attendance_code_posted_ciphertext')->nullable();
            $table->timestamp('attendance_code_posted_at')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only migration: preserve posted attendance history.');
    }
};
