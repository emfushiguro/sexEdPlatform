<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->string('verification_pathway', 64)->nullable()->after('relationship_custom')->index();
            $table->unsignedSmallInteger('current_evidence_round')->default(0)->after('relationship_verified_status');
            $table->timestamp('relationship_deactivated_at')->nullable()->after('relationship_verification_revoked_at');
            $table->index(
                ['parent_user_id', 'relationship_status', 'relationship_verified_status'],
                'pc_guardian_access_idx'
            );
            $table->index(
                ['child_user_id', 'relationship_status', 'relationship_verified_status'],
                'pc_dependent_access_idx'
            );
        });

        Schema::table('guardian_relationship_verification_documents', function (Blueprint $table): void {
            $table->unsignedSmallInteger('submission_round')->default(1)->after('document_type');
            $table->string('document_side', 16)->default('not_applicable')->after('submission_round');
            $table->uuid('pairing_key')->nullable()->after('document_side');
            $table->unsignedSmallInteger('display_order')->default(0)->after('pairing_key');
            $table->char('content_sha256', 64)->nullable()->after('size_bytes');
            $table->timestamp('submitted_at')->nullable()->after('content_sha256');
            $table->timestamp('superseded_at')->nullable()->after('submitted_at');
            $table->unique(
                ['parent_child_account_id', 'submission_round', 'content_sha256'],
                'grvd_round_hash_unique'
            );
            $table->index(
                ['parent_child_account_id', 'submission_round', 'display_order'],
                'grvd_round_order_idx'
            );
        });

        Schema::table('guardian_relationship_verification_audits', function (Blueprint $table): void {
            $table->unsignedSmallInteger('submission_round')->nullable()->after('new_status');
        });

        $pathways = [
            'biological_mother' => 'biological_parent',
            'biological_father' => 'biological_parent',
            'adoptive_parent' => 'adoptive_parent',
            'foster_parent' => 'non_parental_care',
            'grandmother' => 'non_parental_care',
            'grandfather' => 'non_parental_care',
            'aunt' => 'non_parental_care',
            'uncle' => 'non_parental_care',
            'older_sister' => 'non_parental_care',
            'older_brother' => 'non_parental_care',
            'legal_guardian' => 'guardianship',
            'court_appointed_guardian' => 'court_appointed_guardianship',
            'relative' => 'non_parental_care',
            'family_friend' => 'non_parental_care',
            'caregiver' => 'non_parental_care',
            'other' => 'custom_care',
        ];

        DB::table('parent_child_accounts')->orderBy('id')->chunkById(100, function ($rows) use ($pathways): void {
            foreach ($rows as $row) {
                $preserveLegacyAccess = $row->relationship_status === 'active'
                    && $row->verification_status === 'approved'
                    && $row->relationship_verified_at !== null
                    && in_array($row->relationship_verified_status, ['not_required', 'reserved'], true);

                $updates = [
                    'verification_pathway' => $preserveLegacyAccess
                        ? 'legacy'
                        : ($pathways[$row->relationship_type] ?? 'legacy'),
                ];

                if ($preserveLegacyAccess) {
                    $updates['is_legacy_relationship'] = true;
                    $updates['relationship_verified_status'] = 'verified';
                }

                DB::table('parent_child_accounts')->where('id', $row->id)->update($updates);
            }
        });

        DB::table('guardian_relationship_verification_documents')->update([
            'submission_round' => 1,
            'document_side' => 'not_applicable',
            'submitted_at' => DB::raw('created_at'),
        ]);

        DB::table('guardian_relationship_verification_documents')
            ->pluck('parent_child_account_id')
            ->unique()
            ->each(function (int $relationshipId): void {
                DB::table('parent_child_accounts')
                    ->where('id', $relationshipId)
                    ->update(['current_evidence_round' => 1]);
            });
    }

    public function down(): void
    {
        Schema::table('guardian_relationship_verification_audits', function (Blueprint $table): void {
            $table->dropColumn('submission_round');
        });

        Schema::table('guardian_relationship_verification_documents', function (Blueprint $table): void {
            $table->dropUnique('grvd_round_hash_unique');
            $table->dropIndex('grvd_round_order_idx');
            $table->dropColumn([
                'submission_round', 'document_side', 'pairing_key', 'display_order',
                'content_sha256', 'submitted_at', 'superseded_at',
            ]);
        });

        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->dropIndex('pc_guardian_access_idx');
            $table->dropIndex('pc_dependent_access_idx');
            $table->dropIndex(['verification_pathway']);
            $table->dropColumn(['verification_pathway', 'current_evidence_round', 'relationship_deactivated_at']);
        });
    }
};
