<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_parent_registration')->default(false)->after('verified');
            $table->enum('parent_verification_status', ['pending', 'approved', 'rejected'])->nullable()->after('is_parent_registration');
            $table->string('parent_id_document_path')->nullable()->after('parent_verification_status');
            $table->text('parent_verification_rejection_reason')->nullable()->after('parent_id_document_path');
            $table->foreignId('parent_verification_reviewed_by')->nullable()->after('parent_verification_rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('parent_verification_reviewed_at')->nullable()->after('parent_verification_reviewed_by');
            $table->timestamp('parent_verification_approved_at')->nullable()->after('parent_verification_reviewed_at');

            $table->index('parent_verification_status');
        });

        Schema::table('parent_child_accounts', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->after('can_approve_content');
            $table->string('verification_document_path')->nullable()->after('verification_status');
            $table->text('verification_rejection_reason')->nullable()->after('verification_document_path');
            $table->foreignId('verification_reviewed_by')->nullable()->after('verification_rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('verification_reviewed_at')->nullable()->after('verification_reviewed_by');
            $table->timestamp('verification_approved_at')->nullable()->after('verification_reviewed_at');

            $table->index('verification_status');
        });

        // Preserve access for legacy parent-child records created before moderation fields existed.
        DB::table('users')
            ->whereIn('id', function ($query): void {
                $query->select('parent_user_id')->from('parent_child_accounts');
            })
            ->update([
                'is_parent_registration' => true,
                'parent_verification_status' => 'approved',
                'parent_verification_rejection_reason' => null,
                'parent_verification_reviewed_at' => now(),
                'parent_verification_approved_at' => now(),
            ]);

        DB::table('users')
            ->whereIn('id', function ($query): void {
                $query->select('user_id')
                    ->from('learner_profiles')
                    ->where('is_parent_account', true);
            })
            ->update([
                'is_parent_registration' => true,
                'parent_verification_status' => 'approved',
                'parent_verification_rejection_reason' => null,
                'parent_verification_reviewed_at' => now(),
                'parent_verification_approved_at' => now(),
            ]);

        DB::table('parent_child_accounts')->update([
            'verification_status' => 'approved',
            'verification_rejection_reason' => null,
            'verification_reviewed_at' => now(),
            'verification_approved_at' => now(),
        ]);

        DB::table('parent_child_accounts')
            ->whereNull('relationship_verified_at')
            ->update([
                'relationship_verified_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_child_accounts', function (Blueprint $table) {
            $table->dropForeign(['verification_reviewed_by']);
            $table->dropIndex(['verification_status']);
            $table->dropColumn([
                'verification_status',
                'verification_document_path',
                'verification_rejection_reason',
                'verification_reviewed_by',
                'verification_reviewed_at',
                'verification_approved_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_verification_reviewed_by']);
            $table->dropIndex(['parent_verification_status']);
            $table->dropColumn([
                'is_parent_registration',
                'parent_verification_status',
                'parent_id_document_path',
                'parent_verification_rejection_reason',
                'parent_verification_reviewed_by',
                'parent_verification_reviewed_at',
                'parent_verification_approved_at',
            ]);
        });
    }
};
