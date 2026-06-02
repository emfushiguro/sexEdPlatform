<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->foreignId('connector_id')->nullable()->after('user_id')->constrained('connectors')->cascadeOnDelete();
            $table->index(['connector_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropIndex(['connector_id', 'status']);
            $table->dropConstrainedForeignId('connector_id');
        });
    }
};
