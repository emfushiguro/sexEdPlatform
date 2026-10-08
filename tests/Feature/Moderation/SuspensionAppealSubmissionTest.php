<?php

namespace Tests\Feature\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Models\UserSuspension;
use App\Services\Moderation\SuspensionAppealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SuspensionAppealSubmissionTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_default_status_is_pending_review(): void
    {
        $service = app(SuspensionAppealService::class);

        $user = User::factory()->create();
        $suspension = $this->makeSuspension($user, EnforcementActionType::TemporarySuspension, now()->addDays(2));

        $appeal = $service->submitAppeal($suspension, $user, 'I want to request review.');

        $this->assertSame('pending_review', $appeal->status);
    }

    public function test_temporary_and_extended_suspensions_are_appealable(): void
    {
        $service = app(SuspensionAppealService::class);
        $user = User::factory()->create();

        $temporarySuspension = $this->makeSuspension($user, EnforcementActionType::TemporarySuspension, now()->addDays(1));
        $extendedSuspension = $this->makeSuspension($user, EnforcementActionType::ExtendedSuspension, now()->addDays(7));

        $temporaryAppeal = $service->submitAppeal($temporarySuspension, $user, 'Temporary appeal request.');
        $extendedAppeal = $service->submitAppeal($extendedSuspension, $user, 'Extended appeal request.');

        $this->assertNotNull($temporaryAppeal->id);
        $this->assertNotNull($extendedAppeal->id);
    }

    public function test_permanent_suspension_appeal_eligibility_is_admin_controlled(): void
    {
        $service = app(SuspensionAppealService::class);
        $user = User::factory()->create();

        $permanentSuspension = $this->makeSuspension($user, EnforcementActionType::PermanentSuspension, null);

        $this->expectException(\InvalidArgumentException::class);
        $service->submitAppeal($permanentSuspension, $user, 'Please review permanent suspension.');
    }

    public function test_inactive_and_revoked_guardians_cannot_post_as_the_dependent_guardian(): void
    {
        $service = app(SuspensionAppealService::class);
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $guardian = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
        ]);
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'current_evidence_round' => 1,
            'verification_status' => 'approved',
        ]);
        $suspension = $this->makeSuspension($dependent, EnforcementActionType::TemporarySuspension, now()->addDays(2));
        $appeal = $service->submitAppeal($suspension, $dependent, 'Please review this case.');

        $service->postThreadMessage($appeal, $guardian, 'Active guardian follow-up.');

        foreach ([
            [ParentChildAccount::STATUS_INACTIVE, ParentChildAccount::VERIFICATION_VERIFIED],
            [ParentChildAccount::STATUS_REVOKED, ParentChildAccount::VERIFICATION_REVOKED],
        ] as [$relationshipStatus, $verificationStatus]) {
            $relationship->update([
                'relationship_status' => $relationshipStatus,
                'relationship_verified_status' => $verificationStatus,
            ]);

            try {
                $service->postThreadMessage($appeal, $guardian, 'Unauthorized guardian follow-up.');
                $this->fail('Inactive and revoked guardians must not post in the appeal thread.');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function makeSuspension(User $user, EnforcementActionType $type, $endsAt): UserSuspension
    {
        $action = EnforcementAction::query()->create([
            'user_id' => $user->id,
            'action_type' => $type,
            'severity_level' => ViolationSeverity::Major,
            'trigger_type' => 'manual',
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'status' => 'executed',
            'skip_ladder' => false,
        ]);

        return UserSuspension::query()->create([
            'user_id' => $user->id,
            'enforcement_action_id' => $action->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => $endsAt,
            'appeal_status' => 'none',
        ]);
    }
}
