<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('attachment_url');
            $table->timestamp('deleted_at')->nullable()->after('edited_at');
            $table->foreignId('deleted_by_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();

            $table->index('deleted_at', 'messages_deleted_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_deleted_at_idx');
            $table->dropConstrainedForeignId('deleted_by_id');
            $table->dropColumn(['deleted_at', 'edited_at']);
        });
    }
};
