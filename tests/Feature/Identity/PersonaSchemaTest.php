<?php

namespace Tests\Feature\Identity;

use App\Models\GuardianIdentityAudit;
use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;

class PersonaSchemaTest extends TestCase
{
    public function test_persona_tables_keep_only_normalized_identity_metadata(): void
    {
        $this->assertTrue(Schema::hasTable('persona_inquiries'));
        $this->assertTrue(Schema::hasTable('persona_webhook_events'));
        $this->assertTrue(Schema::hasTable('guardian_identity_audits'));
        $this->assertFalse(Schema::hasColumn('persona_inquiries', 'raw_payload'));
        $this->assertFalse(Schema::hasColumn('persona_inquiries', 'document_number'));
        $this->assertFalse(Schema::hasColumn('persona_inquiries', 'media_url'));
    }

    public function test_one_user_can_keep_multiple_attempts_and_link_a_learner_case(): void
    {
        $user = User::factory()->create();
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => $user->id,
            'pathway' => 'teen',
        ]);

        $first = $this->createInquiry($user, $case, 1);
        $second = $this->createInquiry($user, $case, 2);

        $this->assertCount(2, $user->fresh()->personaInquiries);
        $this->assertSame($case->id, $first->learnerVerification->id);
        $this->assertSame($case->id, $second->learnerVerification->id);
        $this->assertSame($user->id, $first->user->id);
    }

    public function test_inquiry_identifiers_are_unique(): void
    {
        $user = User::factory()->create();
        $first = $this->createInquiry($user, null, 1);

        $this->expectException(QueryException::class);
        PersonaInquiry::query()->create([
            ...$first->only([
                'user_id',
                'flow_type',
                'target_submission_round',
                'attempt_number',
                'local_document_type',
                'local_government_id_type',
                'template_key',
                'template_id',
                'persona_inquiry_id',
            ]),
            'reference_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    public function test_guardian_audits_are_append_only(): void
    {
        $guardian = User::factory()->create(['is_parent_registration' => true]);
        $first = GuardianIdentityAudit::query()->create([
            'guardian_id' => $guardian->id,
            'action' => 'submitted',
            'from_status' => null,
            'to_status' => 'pending',
            'attempt_number' => 1,
            'created_at' => now(),
        ]);
        GuardianIdentityAudit::query()->create([
            'guardian_id' => $guardian->id,
            'action' => 'rejected',
            'from_status' => 'pending',
            'to_status' => 'rejected',
            'attempt_number' => 1,
            'created_at' => now(),
        ]);

        $this->assertCount(2, $guardian->fresh()->guardianIdentityAudits);
        $this->assertSame($guardian->id, $first->guardian->id);

        $this->expectException(LogicException::class);
        $first->update(['reason' => 'changed']);
    }

    private function createInquiry(User $user, ?LearnerIdentityVerification $case, int $attemptNumber): PersonaInquiry
    {
        return PersonaInquiry::query()->create([
            'user_id' => $user->id,
            'learner_identity_verification_id' => $case?->id,
            'flow_type' => 'teen_school',
            'target_submission_round' => 1,
            'attempt_number' => $attemptNumber,
            'local_document_type' => 'school_id',
            'template_key' => 'selfie',
            'template_id' => 'itmpl_test',
            'reference_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'persona_inquiry_id' => 'inq_test_'.$attemptNumber.'_'.$user->id,
        ]);
    }
}
