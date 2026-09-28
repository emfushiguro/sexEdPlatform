<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('event_format')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_room')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->string('external_platform')->nullable();
            $table->string('external_platform_name')->nullable();
            $table->text('external_url')->nullable();
            $table->dateTime('external_link_visible_at')->nullable();
            $table->string('external_link_expiry_mode')->default('ongoing');
            $table->dateTime('external_link_expires_at')->nullable();
            $table->dateTime('registration_deadline_at')->nullable();
            $table->string('attendance_code_hash')->nullable();
            $table->boolean('attendance_code_enabled')->default(false);
            $table->dateTime('attendance_code_generated_at')->nullable();
            $table->dateTime('attendance_start_at')->nullable();
            $table->dateTime('attendance_end_at')->nullable();
            $table->dateTime('reminder_sent_for_starts_at')->nullable();
            $table->dateTime('link_available_sent_for_visible_at')->nullable();
        });

        Schema::table('seminar_attendances', function (Blueprint $table): void {
            $table->string('attendance_method')->nullable();
            $table->dateTime('attended_at')->nullable();
        });

        $this->backfill();
    }

    public function backfill(): void
    {
        DB::table('seminars')->where('type', 'physical')->whereNull('event_format')
            ->update(['type' => 'seminar', 'event_format' => 'in_person']);
        DB::table('seminars')->where('type', 'webinar')->whereNull('event_format')
            ->update(['event_format' => 'native']);
        DB::table('seminar_attendances')->whereNull('attendance_method')
            ->update(['attendance_method' => 'native']);
        DB::table('seminar_registrants')->where('status', 'attended')->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('seminar_attendances')->insertOrIgnore([
                        'seminar_id' => $row->seminar_id,
                        'user_id' => $row->user_id,
                        'role' => 'audience',
                        'status' => 'attended',
                        'attendance_method' => 'legacy',
                        'attended_at' => $row->attended_at,
                        'total_seconds' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (DB::table('seminars')->exists() || DB::table('seminar_attendances')->exists()) {
            throw new RuntimeException('Cannot reverse educational event migration while seminar or attendance data exists.');
        }

        Schema::table('seminar_attendances', fn (Blueprint $table) => $table->dropColumn([
            'attendance_method', 'attended_at',
        ]));
        Schema::table('seminars', fn (Blueprint $table) => $table->dropColumn([
            'event_format', 'venue_address', 'venue_room', 'delivery_instructions',
            'external_platform', 'external_platform_name', 'external_url',
            'external_link_visible_at', 'external_link_expiry_mode', 'external_link_expires_at',
            'registration_deadline_at', 'attendance_code_hash', 'attendance_code_enabled',
            'attendance_code_generated_at', 'attendance_start_at', 'attendance_end_at',
            'reminder_sent_for_starts_at', 'link_available_sent_for_visible_at',
        ]));
    }
};
