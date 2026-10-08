<?php

namespace Tests\Feature\DependentSupport;

use App\Models\DependentSupportProfile;
use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DependentSupportVerificationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_profile_does_not_change_approval_or_enter_verification_queries(): void
    {
        Notification::fake();
        $admin = $this->admin();
        [$withSupport, $withProfile] = $this->underReviewRelationship(true);
        [$withoutSupport] = $this->underReviewRelationship(false);
        $profile = $withProfile->dependentSupportProfile()->firstOrFail();
        $before = [
            'health' => $profile->relevant_health_considerations,
            'updated_at' => $profile->getRawOriginal('updated_at'),
        ];
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $approvedWithSupport = app(GuardianRelationshipVerificationService::class)->approve($withSupport, $admin);
        $approvedWithoutSupport = app(GuardianRelationshipVerificationService::class)->approve($withoutSupport, $admin);

        $this->assertSame($approvedWithoutSupport->relationship_status, $approvedWithSupport->relationship_status);
        $this->assertSame($approvedWithoutSupport->relationship_verified_status, $approvedWithSupport->relationship_verified_status);
        $this->assertFalse(collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'dependent_support_profiles')));
        $this->assertSame($before['health'], $profile->fresh()->relevant_health_considerations);
        $this->assertSame($before['updated_at'], $profile->fresh()->getRawOriginal('updated_at'));
    }

    public function test_support_profile_does_not_change_rejection_decisions_or_timestamps(): void
    {
        Notification::fake();
        $admin = $this->admin();
        [$withSupport, $withProfile] = $this->underReviewRelationship(true);
        [$withoutSupport] = $this->underReviewRelationship(false);
        $profile = $withProfile->dependentSupportProfile()->firstOrFail();
        $before = $profile->getRawOriginal('updated_at');
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $rejectedWithSupport = app(GuardianRelationshipVerificationService::class)->reject(
            $withSupport,
            $admin,
            'insufficient_support',
            'More evidence is required.',
            false,
        );
        $rejectedWithoutSupport = app(GuardianRelationshipVerificationService::class)->reject(
            $withoutSupport,
            $admin,
            'insufficient_support',
            'More evidence is required.',
            false,
        );

        $this->assertSame($rejectedWithoutSupport->relationship_status, $rejectedWithSupport->relationship_status);
        $this->assertSame($rejectedWithoutSupport->relationship_verified_status, $rejectedWithSupport->relationship_verified_status);
        $this->assertFalse(collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'dependent_support_profiles')));
        $this->assertSame($before, $profile->fresh()->getRawOriginal('updated_at'));
    }

    /** @return array{0: ParentChildAccount, 1: User} */
    private function underReviewRelationship(bool $withSupport): array
    {
        $guardian = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $dependent = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'aunt',
            'verification_pathway' => 'non_parental_care',
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            'current_evidence_round' => 1,
            'relationship_verification_submitted_at' => now(),
            'verification_status' => 'approved',
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
        ]);
        GuardianRelationshipVerificationDocument::query()->create([
            'parent_child_account_id' => $relationship->id,
            'uploaded_by_user_id' => $guardian->id,
            'document_type' => 'care_arrangement',
            'submission_round' => 1,
            'document_side' => 'not_applicable',
            'display_order' => 0,
            'disk' => 'local',
            'path' => 'isolation/evidence.pdf',
            'original_name' => 'evidence.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'content_sha256' => hash('sha256', 'isolation-evidence-'.$relationship->id),
            'submitted_at' => now(),
        ]);

        if ($withSupport) {
            DependentSupportProfile::query()->create([
                'dependent_user_id' => $dependent->id,
                'relevant_health_considerations' => 'PRIVATE-VERIFICATION-ISOLATION-MARKER',
                'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
                'purpose_acknowledged_at' => now(),
                'purpose_acknowledged_by_user_id' => $dependent->id,
                'created_by_user_id' => $dependent->id,
                'updated_by_user_id' => $dependent->id,
            ]);
        }

        return [$relationship, $dependent];
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        return $admin;
    }
}
