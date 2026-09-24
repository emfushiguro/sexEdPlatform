<?php

namespace Tests\Feature\Identity;

use App\Models\LearnerIdentityAudit;
use App\Models\LearnerIdentityEvidence;
use App\Models\LearnerIdentityVerification;
use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase;

class LearnerIdentitySchemaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_learner_has_one_case_per_pathway_and_three_distinct_evidence_slots(): void
    {
        $user = User::factory()->create();
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => $user->id,
            'pathway' => 'teen',
            'status' => VerificationStatus::Pending->value,
            'submission_round' => 0,
        ]);

        $this->assertSame(VerificationStatus::Pending->value, $case->status);
        $this->assertCount(1, $user->identityVerifications);
        $this->assertSame($user->id, $case->learner->id);

        foreach (['identity_front', 'identity_back', 'selfie'] as $slot) {
            $case->evidence()->create([
                'slot' => $slot,
                'storage_path' => 'learner-verifications/test/'.$slot.'.jpg',
                'mime_type' => 'image/jpeg',
                'byte_size' => 1000,
                'width' => 640,
                'height' => 640,
                'submitted_at' => now(),
            ]);
        }

        $this->assertCount(3, $case->fresh()->evidence);
        $this->expectException(QueryException::class);
        $case->evidence()->create([
            'slot' => 'selfie',
            'storage_path' => 'another.jpg',
            'mime_type' => 'image/jpeg',
            'byte_size' => 1000,
            'width' => 640,
            'height' => 640,
            'submitted_at' => now(),
        ]);
    }

    public function test_a_case_can_start_with_a_null_status(): void
    {
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => User::factory()->create()->id,
            'pathway' => 'teen',
            'status' => null,
        ]);

        $this->assertNull($case->status);
    }

    public function test_case_pathway_is_unique_and_audits_belong_to_the_case(): void
    {
        $user = User::factory()->create();
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => $user->id,
            'pathway' => 'adult',
        ]);
        $case->audits()->create([
            'action' => 'submitted',
            'submission_round' => 1,
            'created_at' => now(),
        ]);

        $this->assertCount(1, $case->fresh()->audits);
        $this->assertSame($case->id, LearnerIdentityAudit::query()->firstOrFail()->verification_id);

        $this->expectException(QueryException::class);
        LearnerIdentityVerification::query()->create([
            'user_id' => $user->id,
            'pathway' => 'adult',
        ]);
    }

    public function test_user_can_have_a_case_for_each_pathway(): void
    {
        $user = User::factory()->create();
        LearnerIdentityVerification::query()->create(['user_id' => $user->id, 'pathway' => 'teen']);
        LearnerIdentityVerification::query()->create(['user_id' => $user->id, 'pathway' => 'adult']);

        $this->assertCount(2, $user->identityVerifications);
    }
}
