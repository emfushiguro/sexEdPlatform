<?php

namespace Tests\Unit\Chat;

use App\Enums\EnrollmentStatus;
use App\Models\Conversation;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Chat\ChatAuthorizationService;
use Tests\TestCase;

class ChatAuthorizationServiceTest extends TestCase
{
    public function test_admin_pairs_are_allowed_without_request_gate(): void
    {
        $service = app(ChatAuthorizationService::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $learner = User::factory()->create(['role' => 'learner']);

        $adminToInstructor = $service->evaluateStart($admin, $instructor);
        $adminToLearner = $service->evaluateStart($admin, $learner);

        $this->assertTrue($adminToInstructor['allowed']);
        $this->assertFalse($adminToInstructor['requires_request']);
        $this->assertTrue($adminToLearner['allowed']);
        $this->assertFalse($adminToLearner['requires_request']);
    }

    public function test_learner_to_instructor_requires_request_when_not_enrolled(): void
    {
        $service = app(ChatAuthorizationService::class);

        $learner = User::factory()->create(['role' => 'learner']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $decision = $service->evaluateStart($learner, $instructor);

        $this->assertTrue($decision['allowed']);
        $this->assertTrue($decision['requires_request']);
    }

    public function test_learner_to_instructor_is_direct_when_enrolled(): void
    {
        $service = app(ChatAuthorizationService::class);

        $learner = User::factory()->create(['role' => 'learner']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'content_owner_type' => 'instructor',
        ]);

        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $decision = $service->evaluateStart($learner, $instructor);

        $this->assertTrue($decision['allowed']);
        $this->assertFalse($decision['requires_request']);
    }

    public function test_instructor_to_learner_is_denied_without_enrollment_relation(): void
    {
        $service = app(ChatAuthorizationService::class);

        $learner = User::factory()->create(['role' => 'learner']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $decision = $service->evaluateStart($instructor, $learner);

        $this->assertFalse($decision['allowed']);
        $this->assertSame('no-enrollment-relation', $decision['reason']);
    }

    public function test_parent_and_linked_child_can_start_direct_chat_without_request_gate(): void
    {
        $service = app(ChatAuthorizationService::class);

        $parent = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
        ]);
        $child = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);

        ParentChildAccount::create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'current_evidence_round' => 1,
            'verification_status' => 'approved',
            'relationship_verified_at' => now(),
        ]);

        $parentToChild = $service->evaluateStart($parent, $child);
        $childToParent = $service->evaluateStart($child, $parent);

        $this->assertTrue($parentToChild['allowed']);
        $this->assertFalse($parentToChild['requires_request']);
        $this->assertTrue($childToParent['allowed']);
        $this->assertFalse($childToParent['requires_request']);
    }

    public function test_parent_and_instructor_are_directly_allowed_when_linked_child_is_enrolled(): void
    {
        $service = app(ChatAuthorizationService::class);

        $parent = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
        ]);
        $child = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => User::STATUS_ACTIVE]);

        ParentChildAccount::create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'current_evidence_round' => 1,
            'verification_status' => 'approved',
            'relationship_verified_at' => now(),
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'content_owner_type' => 'instructor',
        ]);

        ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $parentToInstructor = $service->evaluateStart($parent, $instructor);
        $instructorToParent = $service->evaluateStart($instructor, $parent);

        $this->assertTrue($parentToInstructor['allowed']);
        $this->assertFalse($parentToInstructor['requires_request']);
        $this->assertTrue($instructorToParent['allowed']);
        $this->assertFalse($instructorToParent['requires_request']);
    }

    public function test_only_access_eligible_guardian_links_qualify_for_child_and_instructor_chat(): void
    {
        $service = app(ChatAuthorizationService::class);

        $parent = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $child = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => User::STATUS_ACTIVE]);
        $parent->forceFill(['parent_verification_status' => 'approved'])->save();

        $relationship = ParentChildAccount::create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            'verification_status' => 'approved',
            'relationship_verified_at' => now(),
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'content_owner_type' => 'instructor',
        ]);
        ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $this->assertFalse($service->evaluateStart($parent, $child)['allowed']);
        $pendingParentToInstructor = $service->evaluateStart($parent, $instructor);
        $this->assertTrue($pendingParentToInstructor['allowed']);
        $this->assertTrue($pendingParentToInstructor['requires_request']);

        $relationship->update([
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'verification_status' => 'approved',
            'current_evidence_round' => 1,
            'relationship_verified_at' => now(),
        ]);

        $this->assertTrue($service->evaluateStart($parent, $child)['allowed']);
        $this->assertTrue($service->evaluateStart($parent, $instructor)['allowed']);

        $relationship->update([
            'relationship_status' => ParentChildAccount::STATUS_REVOKED,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_REVOKED,
        ]);

        $this->assertFalse($service->evaluateStart($parent, $child)['allowed']);
        $revokedParentToInstructor = $service->evaluateStart($parent, $instructor);
        $this->assertTrue($revokedParentToInstructor['allowed']);
        $this->assertTrue($revokedParentToInstructor['requires_request']);
    }

    public function test_send_and_subscribe_require_participation_and_active_state(): void
    {
        $service = app(ChatAuthorizationService::class);

        $userA = User::factory()->create(['role' => 'learner']);
        $userB = User::factory()->create(['role' => 'instructor']);
        $userC = User::factory()->create(['role' => 'admin']);

        $activeConversation = Conversation::create([
            'participant_one_id' => $userA->id,
            'participant_two_id' => $userB->id,
            'pair_key' => Conversation::makePairKey($userA->id, $userB->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        $pendingConversation = Conversation::create([
            'participant_one_id' => $userA->id,
            'participant_two_id' => $userB->id,
            'pair_key' => Conversation::makePairKey($userA->id, $userB->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_PENDING_REQUEST,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null).':pending',
        ]);

        $this->assertTrue($service->canSubscribeToConversation($userA, $activeConversation));
        $this->assertFalse($service->canSubscribeToConversation($userC, $activeConversation));

        $this->assertTrue($service->canSendMessage($userA, $activeConversation));
        $this->assertFalse($service->canSendMessage($userA, $pendingConversation));
        $this->assertFalse($service->canSendMessage($userC, $activeConversation));
    }

    public function test_existing_guardian_chat_keeps_transcript_access_after_revocation_but_loses_live_access(): void
    {
        $service = app(ChatAuthorizationService::class);

        $guardian = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $child = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);

        $relationship = ParentChildAccount::create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $child->id,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
        ]);

        $conversation = Conversation::create([
            'participant_one_id' => $guardian->id,
            'participant_two_id' => $child->id,
            'pair_key' => Conversation::makePairKey($guardian->id, $child->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        $this->assertTrue($service->canViewConversation($guardian, $conversation));
        $this->assertTrue($service->canSubscribeToConversation($guardian, $conversation));
        $this->assertTrue($service->canSendMessage($guardian, $conversation));

        $relationship->update([
            'relationship_status' => ParentChildAccount::STATUS_REVOKED,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_REVOKED,
        ]);

        $revokedConversation = $conversation->fresh();

        $this->assertTrue($service->canViewConversation($guardian, $revokedConversation));
        $this->assertFalse($service->canSubscribeToConversation($guardian, $revokedConversation));
        $this->assertFalse($service->canSendMessage($guardian, $revokedConversation));
    }

    public function test_suspended_participant_loses_transcript_live_and_send_access(): void
    {
        $service = app(ChatAuthorizationService::class);

        $guardian = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $child = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);

        ParentChildAccount::create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $child->id,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
        ]);

        $conversation = Conversation::create([
            'participant_one_id' => $guardian->id,
            'participant_two_id' => $child->id,
            'pair_key' => Conversation::makePairKey($guardian->id, $child->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        $guardian->update(['status' => User::STATUS_SUSPENDED]);
        $suspendedGuardian = $guardian->fresh();

        $this->assertFalse($service->canViewConversation($suspendedGuardian, $conversation));
        $this->assertFalse($service->canSubscribeToConversation($suspendedGuardian, $conversation));
        $this->assertFalse($service->canSendMessage($suspendedGuardian, $conversation));
    }
}
