<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('platform_feedback')->where('status', 'planned')->update(['status' => 'reviewed']);
        DB::table('platform_feedback')->where('status', 'archived')->update(['status' => 'closed']);

        DB::table('platform_feedback_histories')->where('from_status', 'planned')->update(['from_status' => 'reviewed']);
        DB::table('platform_feedback_histories')->where('to_status', 'planned')->update(['to_status' => 'reviewed']);
        DB::table('platform_feedback_histories')->where('from_status', 'archived')->update(['from_status' => 'closed']);
        DB::table('platform_feedback_histories')->where('to_status', 'archived')->update(['to_status' => 'closed']);
    }

    public function down(): void
    {
        // Canonical rows cannot be reliably distinguished from normalized legacy rows.
    }
};
