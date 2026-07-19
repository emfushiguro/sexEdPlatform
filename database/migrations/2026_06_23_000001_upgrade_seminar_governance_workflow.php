<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->string('custom_category')->nullable()->after('category');
            $table->dateTime('submitted_for_review_at')->nullable()->after('admin_moderation_reason');
            $table->foreignId('submitted_for_review_by')->nullable()->after('submitted_for_review_at')->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable()->after('submitted_for_review_by');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable()->after('rejected_by');
            $table->text('moderator_note')->nullable()->after('rejection_reason');
            $table->dateTime('published_at')->nullable()->after('moderator_note');
            $table->foreignId('published_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->dateTime('archived_at')->nullable()->after('published_by');
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();

            $table->index('custom_category');
            $table->index('submitted_for_review_at');
            $table->index('approved_at');
            $table->index('published_at');
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table): void {
            $table->dropIndex(['custom_category']);
            $table->dropIndex(['submitted_for_review_at']);
            $table->dropIndex(['approved_at']);
            $table->dropIndex(['published_at']);
            $table->dropIndex(['archived_at']);
            $table->dropForeign(['submitted_for_review_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['published_by']);
            $table->dropForeign(['archived_by']);
            $table->dropColumn([
                'custom_category',
                'submitted_for_review_at',
                'submitted_for_review_by',
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
                'moderator_note',
                'published_at',
                'published_by',
                'archived_at',
                'archived_by',
            ]);
        });
    }
};
