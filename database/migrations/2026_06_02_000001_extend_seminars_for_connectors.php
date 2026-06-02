<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table) {
            $table->foreignId('connector_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('type')->default('webinar')->after('connector_id');
            $table->text('purpose')->nullable()->after('description');
            $table->string('category')->default('education')->after('purpose');
            $table->string('status')->default('draft')->after('category');
            $table->dateTime('starts_at')->nullable()->after('schedule');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
            $table->unsignedInteger('capacity')->nullable()->after('ends_at');
            $table->string('target_participants')->default('learners_and_instructors')->after('capacity');
            $table->json('learner_age_categories')->nullable()->after('target_participants');
            $table->string('livestream_channel')->nullable()->after('learner_age_categories');
            $table->dateTime('cancelled_at')->nullable()->after('livestream_channel');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by');
            $table->dateTime('completed_at')->nullable()->after('cancellation_reason');
            $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->string('admin_moderation_status')->default('clear')->after('completed_by');
            $table->text('admin_moderation_reason')->nullable()->after('admin_moderation_status');

            $table->index('connector_id');
            $table->index('status');
            $table->index('type');
            $table->index('starts_at');
            $table->index('category');
            $table->index('admin_moderation_status');
        });

        Schema::table('seminar_registrants', function (Blueprint $table) {
            $table->string('participant_type')->nullable()->after('status');
            $table->dateTime('cancelled_at')->nullable()->after('attended_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('seminar_registrants', function (Blueprint $table) {
            $table->dropColumn(['participant_type', 'cancelled_at', 'cancellation_reason']);
        });

        Schema::table('seminars', function (Blueprint $table) {
            $table->dropForeign(['connector_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn([
                'connector_id',
                'type',
                'purpose',
                'category',
                'status',
                'starts_at',
                'ends_at',
                'capacity',
                'target_participants',
                'learner_age_categories',
                'livestream_channel',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
                'completed_at',
                'completed_by',
                'admin_moderation_status',
                'admin_moderation_reason',
            ]);
        });
    }
};
