<?php

use App\Enums\EnrollmentStatus;
use App\Models\ModuleAccessGrant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_access_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('connector_id')->nullable()->constrained('connectors')->nullOnDelete();
            $table->string('status', 32)->default(ModuleAccessGrant::STATUS_PENDING);
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id'], 'uq_module_access_grant_source');
            $table->unique(['user_id', 'module_id', 'source_type', 'source_id'], 'uq_module_access_grant_identity');
            $table->index(['user_id', 'module_id', 'status'], 'idx_module_access_grant_access');
        });

        Schema::table('module_enrollments', function (Blueprint $table): void {
            $table->text('inactive_reason')->nullable()->after('status');
            $table->timestamp('inactivated_at')->nullable()->after('inactive_reason');
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement("UPDATE module_enrollments SET status = status");
        } else {
            DB::statement(
                "ALTER TABLE module_enrollments MODIFY COLUMN status ENUM('pending','approved','rejected','pending_parent_approval','inactive') NOT NULL DEFAULT 'approved'"
            );
        }

        DB::table('module_enrollments')
            ->select([
                'id',
                'user_id',
                'module_id',
                'status',
                'enrolled_at',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id')
            ->each(function (object $enrollment): void {
                $status = match ((string) $enrollment->status) {
                    EnrollmentStatus::Approved->value => ModuleAccessGrant::STATUS_ACTIVE,
                    EnrollmentStatus::Pending->value,
                    EnrollmentStatus::PendingParentApproval->value => ModuleAccessGrant::STATUS_PENDING,
                    default => ModuleAccessGrant::STATUS_INACTIVE,
                };

                DB::table('module_access_grants')->insertOrIgnore([
                    'user_id' => $enrollment->user_id,
                    'module_id' => $enrollment->module_id,
                    'source_type' => ModuleAccessGrant::SOURCE_DIRECT_ENROLLMENT,
                    'source_id' => $enrollment->id,
                    'connector_id' => null,
                    'status' => $status,
                    'granted_at' => $enrollment->enrolled_at ?? $enrollment->created_at,
                    'ended_at' => $status === ModuleAccessGrant::STATUS_INACTIVE ? $enrollment->updated_at ?? now() : null,
                    'reason' => 'migration_backfill',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        DB::table('module_purchases')
            ->where('status', 'completed')
            ->select(['id', 'user_id', 'module_id', 'purchased_at', 'created_at'])
            ->orderBy('id')
            ->each(function (object $purchase): void {
                DB::table('module_access_grants')->insertOrIgnore([
                    'user_id' => $purchase->user_id,
                    'module_id' => $purchase->module_id,
                    'source_type' => ModuleAccessGrant::SOURCE_INDIVIDUAL_PURCHASE,
                    'source_id' => $purchase->id,
                    'connector_id' => null,
                    'status' => ModuleAccessGrant::STATUS_ACTIVE,
                    'granted_at' => $purchase->purchased_at ?? $purchase->created_at,
                    'ended_at' => null,
                    'reason' => 'migration_backfill',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('module_access_grants') && DB::table('module_access_grants')->exists()) {
            throw new RuntimeException('Refusing to drop non-empty module access grants.');
        }

        if (Schema::hasColumn('module_enrollments', 'status')
            && DB::table('module_enrollments')->where('status', 'inactive')->exists()) {
            throw new RuntimeException('Refusing to remove the inactive enrollment state while it is in use.');
        }

        if (Schema::hasColumn('module_enrollments', 'status') && DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement(
                "ALTER TABLE module_enrollments MODIFY COLUMN status ENUM('pending','approved','rejected','pending_parent_approval') NOT NULL DEFAULT 'approved'"
            );
        }

        Schema::table('module_enrollments', function (Blueprint $table): void {
            $table->dropColumn(['inactive_reason', 'inactivated_at']);
        });
        Schema::dropIfExists('module_access_grants');
    }
};
