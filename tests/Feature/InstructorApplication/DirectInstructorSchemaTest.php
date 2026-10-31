<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorApplication;
use App\Models\InstructorIdentityAudit;
use App\Models\InstructorIdentityVerification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class DirectInstructorSchemaTest extends TestCase
{
    public function test_direct_instructor_schema_is_additive_and_feature_is_disabled_by_default(): void
    {
        $this->assertTrue(Schema::hasColumns('user_profiles', [
            'province_code',
            'city_code',
            'barangay_code',
            'address_line',
        ]));
        $this->assertTrue(Schema::hasTable('instructor_identity_verifications'));
        $this->assertTrue(Schema::hasTable('instructor_identity_audits'));
        $this->assertTrue(Schema::hasColumns('instructor_identity_verifications', [
            'user_id',
            'source',
            'status',
            'submission_round',
            'declared_snapshot',
            'comparison_summary',
            'sandbox_manual_review',
            'requested_action_codes',
            'superseded_at',
        ]));
        $this->assertTrue(Schema::hasColumns('instructor_identity_audits', [
            'instructor_identity_verification_id',
            'actor_id',
            'persona_inquiry_id',
            'metadata',
        ]));
        $this->assertTrue(Schema::hasColumns('persona_inquiries', [
            'instructor_identity_verification_id',
        ]));
        $this->assertTrue(Schema::hasColumns('instructor_applications', [
            'instructor_identity_verification_id',
            'submitted_at',
            'document_disk',
        ]));
        $this->assertFalse((bool) config('instructor_application.enabled'));
        $this->assertSame(18, config('instructor_application.minimum_age'));
        $this->assertSame('402100000', config('instructor_application.province_code'));
        $this->assertSame('local', config('instructor_application.document_disk'));
    }

    public function test_existing_pending_application_can_remain_without_identity_link(): void
    {
        $user = User::factory()->create();

        $application = InstructorApplication::query()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'government_id_path' => 'legacy/government-id.pdf',
            'clearance_path' => 'legacy/clearance.pdf',
            'cv_resume_path' => 'legacy/cv.pdf',
            'bio' => 'Existing pending application.',
        ]);

        $this->assertNull($application->instructor_identity_verification_id);
        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'pending',
            'instructor_identity_verification_id' => null,
            'document_disk' => 'public',
        ]);
    }

    public function test_identity_snapshots_round_trip_and_current_scopes_use_indexed_history(): void
    {
        $user = User::factory()->create();

        $first = InstructorIdentityVerification::query()->create([
            'user_id' => $user->id,
            'source' => 'direct_persona',
            'status' => 'awaiting_verification',
            'submission_round' => 1,
            'declared_snapshot' => [
                'first_name' => 'Ada',
                'birthdate' => '1990-01-01',
            ],
            'comparison_summary' => [
                ['field' => 'first_name', 'outcome' => 'match', 'normalization_version' => 'v1'],
            ],
            'requested_action_codes' => ['replace_persona_attempt'],
        ]);
        $second = InstructorIdentityVerification::query()->create([
            'user_id' => $user->id,
            'source' => 'direct_persona',
            'status' => 'pending_review',
            'submission_round' => 2,
        ]);
        $approved = InstructorIdentityVerification::query()->create([
            'user_id' => $user->id,
            'source' => 'direct_persona',
            'status' => 'approved',
            'submission_round' => 3,
        ]);

        $this->assertSame(['first_name' => 'Ada', 'birthdate' => '1990-01-01'], $first->fresh()->declared_snapshot);
        $this->assertSame('match', $first->fresh()->comparison_summary[0]['outcome']);
        $this->assertSame(['replace_persona_attempt'], $first->fresh()->requested_action_codes);
        $this->assertSame($approved->id, $user->fresh()->instructorIdentityVerifications()->current()->latest('submission_round')->value('id'));
        $this->assertSame($second->id, $user->fresh()->instructorIdentityVerifications()->pendingReview()->value('id'));
        $this->assertSame($approved->id, $user->fresh()->instructorIdentityVerifications()->accessApproved()->value('id'));
    }

    public function test_user_applicant_classification_is_not_a_learner(): void
    {
        $user = User::factory()->create(['role' => 'applicant', 'account_type' => User::ACCOUNT_TYPE_APPLICANT]);

        $this->assertTrue($user->isInstructorApplicant());
        $this->assertFalse($user->isLearner());
    }

    public function test_duplicate_identity_round_is_rejected(): void
    {
        $user = User::factory()->create();
        InstructorIdentityVerification::query()->create([
            'user_id' => $user->id,
            'source' => 'direct_persona',
            'status' => 'awaiting_verification',
            'submission_round' => 1,
        ]);

        $this->expectException(QueryException::class);
        InstructorIdentityVerification::query()->create([
            'user_id' => $user->id,
            'source' => 'direct_persona',
            'status' => 'awaiting_verification',
            'submission_round' => 1,
        ]);
    }

    public function test_identity_audits_are_append_only(): void
    {
        $user = User::factory()->create();
        $case = InstructorIdentityVerification::query()->create([
            'user_id' => $user->id,
            'source' => 'direct_persona',
            'status' => 'pending_review',
            'submission_round' => 1,
        ]);
        $audit = InstructorIdentityAudit::query()->create([
            'instructor_identity_verification_id' => $case->id,
            'actor_id' => $user->id,
            'action' => 'submitted',
            'from_status' => 'verification_in_progress',
            'to_status' => 'pending_review',
            'submission_round' => 1,
            'metadata' => ['normalization_version' => 'v1'],
            'created_at' => now(),
        ]);

        $this->expectException(LogicException::class);
        $audit->update(['reason' => 'changed']);
    }

    public function test_identity_audits_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $case = InstructorIdentityVerification::query()->create([
            'user_id' => $user->id,
            'source' => 'direct_persona',
            'status' => 'pending_review',
            'submission_round' => 1,
        ]);
        $audit = InstructorIdentityAudit::query()->create([
            'instructor_identity_verification_id' => $case->id,
            'action' => 'submitted',
            'submission_round' => 1,
            'created_at' => now(),
        ]);

        $this->expectException(LogicException::class);
        $audit->delete();
    }
}
