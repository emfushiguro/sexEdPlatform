<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_speakers', function (Blueprint $table): void {
            $table->string('status')->default('accepted')->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('responded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('seminar_speakers', function (Blueprint $table): void {
            $table->dropColumn(['status', 'invited_at', 'responded_at']);
        });
    }
};
