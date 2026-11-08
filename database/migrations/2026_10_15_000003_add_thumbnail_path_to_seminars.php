<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('thumbnail_path')->nullable();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Forward-only migration: preserve seminar thumbnail paths.');
    }
};
