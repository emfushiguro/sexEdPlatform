<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('platform_feedback')
            ->whereNotNull('staff_response')
            ->where('staff_response', '!=', '')
            ->orderBy('id')
            ->each(function (object $ticket): void {
                $createdAt = $ticket->reviewed_at ?? $ticket->updated_at ?? now();
                $exists = DB::table('platform_feedback_messages')
                    ->where('platform_feedback_id', $ticket->id)
                    ->where('sender_role', 'admin')
                    ->where('body', $ticket->staff_response)
                    ->where('created_at', $createdAt)
                    ->exists();

                if (! $exists) {
                    DB::table('platform_feedback_messages')->insert([
                        'platform_feedback_id' => $ticket->id,
                        'sender_id' => $ticket->reviewed_by,
                        'sender_role' => 'admin',
                        'body' => $ticket->staff_response,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Migrated messages are retained because they may have joined an active conversation.
    }
};
