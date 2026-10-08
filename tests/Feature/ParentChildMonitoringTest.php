<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\GuardianRelationshipVerificationAudit;
use App\Models\ParentChildAccount;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\RewardLog;
use App\Models\User;
use App\Models\UserGamification;
use App\Services\Admin\UserRelationshipService;
use App\Services\GuardianRelationshipVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParentChildMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_enrollments_accepts_pending_parent_approval_status(): void
    {
        $parent = User::factory()->create(['email_verified_at' => now()]);
        $parent->assignRole('learner');
        $parent->forceFill([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
        ])->save();

        $child = User::factory()->create(['email_verified_at' => now()]);
        $child->assignRole('learner');
        $child->forceFill(['status' => User::STATUS_ACTIVE])->save();

        $module = Module::factory()->create();

        $enrollment = ModuleEnrollment::create([
            'user_id'    => $child->id,
            'module_id'  => $module->id,
            'status'     => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->assertDatabaseHas('module_enrollments', [
            'id'     => $enrollment->id,
            'status' => 'pending_parent_approval',
        ]);
    }

    private function createParentWithChild(): array
    {
        $parent = User::factory()->create(['email_verified_at' => now()]);
        $parent->assignRole('learner');
        $parent->forceFill([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
        ])->save();

        $child = User::factory()->create(['email_verified_at' => now()]);
        $child->assignRole('learner');
        $child->forceFill(['status' => User::STATUS_ACTIVE])->save();

        ParentChildAccount::create([
            'parent_user_id'        => $parent->id,
            'child_user_id'         => $child->id,
            'can_view_progress'     => true,
            'can_view_quiz_answers' => true,
            'can_approve_content'   => true,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'current_evidence_round' => 1,
            'verification_status'   => 'approved',
            'relationship_verified_at' => now(),
        ]);

        UserGamification::create([
            'user_id'      => $child->id,
            'level'        => 1,
            'score'        => 0,
            'total_points' => 0,
            'streak_count' => 0,
        ]);

        return [$parent, $child];
    }

    public function test_parent_can_view_own_childs_detail_page(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $this->actingAs($parent)
             ->get(route('parent.children.show', $child))
             ->assertOk();
    }

    public function test_pending_rejected_inactive_and_revoked_relationships_cannot_view_progress(): void
    {
        [$parent, $child] = $this->createParentWithChild();
        $relationship = ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->firstOrFail();

        foreach ([
            [ParentChildAccount::STATUS_PENDING, ParentChildAccount::VERIFICATION_UNDER_REVIEW],
            [ParentChildAccount::STATUS_REJECTED, ParentChildAccount::VERIFICATION_REJECTED],
            [ParentChildAccount::STATUS_INACTIVE, ParentChildAccount::VERIFICATION_VERIFIED],
            [ParentChildAccount::STATUS_REVOKED, ParentChildAccount::VERIFICATION_REVOKED],
        ] as [$relationshipStatus, $verificationStatus]) {
            $relationship->update([
                'relationship_status' => $relationshipStatus,
                'relationship_verified_status' => $verificationStatus,
            ]);

            $this->actingAs($parent)
                ->get(route('parent.children.show', $child))
                ->assertForbidden();
        }
    }

    public function test_verified_relationship_without_quiz_permission_cannot_open_an_attempt(): void
    {
        [$parent, $child] = $this->createParentWithChild();
        ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->update(['can_view_quiz_answers' => false]);

        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::create([
            'user_id' => $child->id,
            'quiz_id' => $quiz->id,
            'score' => 85,
            'passed' => true,
            'answers' => [],
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->actingAs($parent)
            ->get(route('parent.children.quiz-attempts.show', [$child, $attempt]))
            ->assertForbidden();
    }

    public function test_suspended_guardian_loses_access_without_mutating_other_relationships(): void
    {
        [$parent, $child] = $this->createParentWithChild();
        $otherChild = User::factory()->create([
            'email_verified_at' => now(),
            'status' => User::STATUS_ACTIVE,
        ]);
        $otherChild->assignRole('learner');
        $otherRelationship = ParentChildAccount::create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $otherChild->id,
            'can_view_progress' => true,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'current_evidence_round' => 1,
            'verification_status' => 'approved',
            'relationship_verified_at' => now(),
        ]);

        $parent->update(['status' => User::STATUS_SUSPENDED]);

        $this->actingAs($parent)
            ->get(route('parent.children.show', $child))
            ->assertForbidden();

        $this->assertDatabaseHas('parent_child_accounts', [
            'id' => $otherRelationship->id,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
        ]);
    }

    public function test_multiple_guardians_have_independent_review_permissions_and_revocation(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');

        $dependent = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $dependent->assignRole('learner');

        $guardians = collect(['a', 'b'])->map(function (): User {
            $guardian = User::factory()->create([
                'role' => 'learner',
                'status' => User::STATUS_ACTIVE,
                'is_parent_registration' => true,
                'parent_verification_status' => 'approved',
            ]);
            $guardian->assignRole('learner');

            return $guardian;
        });

        $verificationService = app(GuardianRelationshipVerificationService::class);
        $relationships = $guardians->map(function (User $guardian, int $index) use ($admin, $dependent, $verificationService): ParentChildAccount {
            $relationship = ParentChildAccount::query()->create([
                'parent_user_id' => $guardian->id,
                'child_user_id' => $dependent->id,
                'relationship_type' => 'legal_guardian',
                'verification_pathway' => 'guardianship',
                'relationship_status' => ParentChildAccount::STATUS_PENDING,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
                'current_evidence_round' => 0,
                'can_view_progress' => true,
                'can_view_quiz_answers' => true,
                'can_approve_content' => false,
                'verification_status' => 'approved',
            ]);

            $submitted = $verificationService->submit($relationship, $guardian, [[
                'document_type' => 'court_order',
                'document_side' => 'not_applicable',
                'pairing_key' => null,
                'file' => UploadedFile::fake()->create("guardian-{$index}.pdf", 100, 'application/pdf'),
            ]], null);

            return $verificationService->approve($submitted, $admin);
        });

        [$guardianARelationship, $guardianBRelationship] = $relationships->values()->all();
        $this->assertTrue($guardianARelationship->isVerifiedActive());
        $this->assertTrue($guardianBRelationship->isVerifiedActive());

        $relationshipService = app(UserRelationshipService::class);
        $relationshipService->updateParentChildPermissions(
            $guardianARelationship->parent_user_id,
            $guardianARelationship->child_user_id,
            [
                'can_view_progress' => true,
                'can_view_quiz_answers' => false,
                'can_approve_content' => false,
            ],
            $admin,
        );
        $relationshipService->updateParentChildPermissions(
            $guardianBRelationship->parent_user_id,
            $guardianBRelationship->child_user_id,
            [
                'can_view_progress' => false,
                'can_view_quiz_answers' => true,
                'can_approve_content' => true,
            ],
            $admin,
        );

        $guardianARelationship = $guardianARelationship->fresh();
        $guardianBRelationship = $guardianBRelationship->fresh();
        $this->assertFalse($guardianARelationship->can_view_quiz_answers);
        $this->assertTrue($guardianBRelationship->can_approve_content);

        $verificationService->revoke(
            $guardianARelationship,
            $admin,
            'cannot_verify',
            'Guardian A evidence was invalidated.',
        );

        $this->assertSame(2, ParentChildAccount::query()->where('child_user_id', $dependent->id)->count());
        $this->assertFalse(ParentChildAccount::accessEligible()->whereKey($guardianARelationship->getKey())->exists());
        $this->assertTrue(ParentChildAccount::accessEligible()->whereKey($guardianBRelationship->getKey())->exists());
        $this->assertDatabaseHas('users', ['id' => $dependent->id, 'status' => User::STATUS_ACTIVE]);
        $this->assertDatabaseHas('guardian_relationship_verification_audits', [
            'parent_child_account_id' => $guardianARelationship->id,
            'action' => 'revoked',
        ]);
        $this->assertTrue(GuardianRelationshipVerificationAudit::query()
            ->where('parent_child_account_id', $guardianARelationship->id)
            ->where('action', 'permissions_updated')
            ->exists());
        $this->assertTrue(GuardianRelationshipVerificationAudit::query()
            ->where('parent_child_account_id', $guardianBRelationship->id)
            ->where('action', 'permissions_updated')
            ->exists());
        $this->assertFalse(GuardianRelationshipVerificationAudit::query()
            ->where('parent_child_account_id', $guardianBRelationship->id)
            ->where('action', 'revoked')
            ->exists());
    }

    public function test_parent_can_view_quiz_attempt_details_for_owned_child(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::create([
            'user_id' => $child->id,
            'quiz_id' => $quiz->id,
            'score' => 85,
            'passed' => true,
            'answers' => [],
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->actingAs($parent)
            ->get(route('parent.children.quiz-attempts.show', [$child, $attempt]))
            ->assertOk()
            ->assertSee('Quiz Attempt Details');
    }

    public function test_parent_can_view_pending_enrollment_details_for_owned_child(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create([
            'title' => 'Body Safety Basics',
            'enrollment_mode' => 'auto',
            'access_type' => 'free',
        ]);

        $enrollment = ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Lesson Parent Review',
            'order' => 1,
            'text_content' => '<p>Lesson overview for parent review.</p>',
            'is_published' => true,
        ]);

        LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Topic Parent Review',
            'type' => 'text',
            'text_content' => '<p>Topic details visible to parent.</p>',
            'order' => 1,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'title' => 'Quiz Parent Review',
            'is_active' => true,
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is consent? ',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order' => 1,
        ]);

        QuizOption::create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Consent is clear agreement.',
            'is_correct' => true,
            'order' => 1,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.children.enrollments.show', [$child, $enrollment]))
            ->assertOk()
            ->assertSee('Lesson Details')
            ->assertSee('Body Safety Basics')
            ->assertSee('Learning Content Review')
            ->assertSee('Lesson Parent Review')
            ->assertSee('Topic Parent Review')
            ->assertSee('Review Topic')
            ->assertSee('Lesson Quizzes');
    }

    public function test_parent_dashboard_shows_pending_parent_approval_label_and_detail_link(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create(['title' => 'Body Boundaries']);
        $enrollment = ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $response = $this->actingAs($parent)
            ->get(route('parent.children.show', $child));

        $response->assertOk()
            ->assertSee('Pending Guardian Approval')
            ->assertSee(route('parent.children.enrollments.show', [$child, $enrollment]), false);
    }

    public function test_parent_enrollment_detail_shows_notification_context_when_opened_from_notification(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create(['title' => 'Consent Basics']);
        $enrollment = ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.children.enrollments.show', [$child, $enrollment, 'from' => 'notification']))
            ->assertOk()
            ->assertSee('Opened from notification')
            ->assertSee('Return to notifications');
    }

    public function test_parent_without_content_approval_cannot_view_pending_enrollment_details(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->update(['can_approve_content' => false]);

        $module = Module::factory()->create();
        $enrollment = ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.children.enrollments.show', [$child, $enrollment]))
            ->assertForbidden();
    }

    public function test_parent_cannot_view_quiz_attempt_details_when_attempt_belongs_to_another_child(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $otherChild = User::factory()->create(['email_verified_at' => now()]);
        $otherChild->assignRole('learner');

        $quiz = Quiz::factory()->create();
        $attempt = QuizAttempt::create([
            'user_id' => $otherChild->id,
            'quiz_id' => $quiz->id,
            'score' => 70,
            'passed' => true,
            'answers' => [],
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->actingAs($parent)
            ->get(route('parent.children.quiz-attempts.show', [$child, $attempt]))
            ->assertNotFound();
    }

    public function test_parent_cannot_view_another_users_child(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        /** @var User $stranger */
        $stranger = User::factory()->create(['email_verified_at' => now()]);
        $stranger->assignRole('learner');

        $this->actingAs($stranger)
             ->get(route('parent.children.show', $child))
             ->assertForbidden();
    }

    public function test_parent_cannot_view_child_when_relationship_is_archived(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->delete();

        $this->actingAs($parent)
            ->get(route('parent.children.show', $child))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_parent_routes(): void
    {
        $child = User::factory()->create();

        $this->get(route('parent.children.show', $child))
             ->assertRedirect('/login');
    }

    public function test_get_progress_returns_approved_enrollments_with_progress(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create(['title' => 'Test Module']);
        ModuleEnrollment::create([
            'user_id'     => $child->id,
            'module_id'   => $module->id,
            'status'      => 'approved',
            'enrolled_at' => now(),
        ]);

        $service = app(\App\Services\ParentChildService::class);
        $progress = $service->getProgress($child);

        $this->assertCount(1, $progress);
        $this->assertEquals('Test Module', $progress->first()->module->title);
    }

    public function test_get_quiz_results_returns_attempts_newest_first(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $quiz = Quiz::factory()->create();

        QuizAttempt::create([
            'user_id'      => $child->id,
            'quiz_id'      => $quiz->id,
            'score'        => 80,
            'passed'       => true,
            'answers'      => json_encode([]),
            'started_at'   => now()->subMinutes(10),
            'completed_at' => now(),
        ]);

        $service = app(\App\Services\ParentChildService::class);
        $results = $service->getQuizResults($child);

        $this->assertCount(1, $results);
        $this->assertEquals(80, $results->first()->score);
    }

    public function test_get_achievements_returns_gamification_and_reward_logs(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $service = app(\App\Services\ParentChildService::class);
        $achievements = $service->getAchievements($child);

        $this->assertArrayHasKey('gamification', $achievements);
        $this->assertArrayHasKey('rewardLogs', $achievements);
        $this->assertEquals(1, $achievements['gamification']->level);
    }

    public function test_get_pending_enrollments_returns_only_pending_parent_approval(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create();
        ModuleEnrollment::create([
            'user_id'     => $child->id,
            'module_id'   => $module->id,
            'status'      => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $service = app(\App\Services\ParentChildService::class);
        $pending = $service->getPendingEnrollments($child);

        $this->assertCount(1, $pending);
        $this->assertEquals('pending_parent_approval', $pending->first()->status->value);
    }

    public function test_parent_can_approve_pending_enrollment_auto_module(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create(['enrollment_mode' => 'auto']);
        $enrollment = ModuleEnrollment::create([
            'user_id'     => $child->id,
            'module_id'   => $module->id,
            'status'      => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->actingAs($parent)
             ->post(route('parent.children.enrollments.approve', [$child, $enrollment]))
             ->assertRedirect(route('parent.children.show', $child));

        $this->assertDatabaseHas('module_enrollments', [
            'id'     => $enrollment->id,
            'status' => 'approved',
        ]);
    }

    public function test_parent_can_approve_pending_enrollment_manual_module(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create(['enrollment_mode' => 'manual']);
        $enrollment = ModuleEnrollment::create([
            'user_id'     => $child->id,
            'module_id'   => $module->id,
            'status'      => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->actingAs($parent)
             ->post(route('parent.children.enrollments.approve', [$child, $enrollment]))
             ->assertRedirect(route('parent.children.show', $child));

        $this->assertDatabaseHas('module_enrollments', [
            'id'     => $enrollment->id,
            'status' => 'pending',
        ]);
    }

    public function test_parent_without_content_approval_cannot_approve_pending_enrollment(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->update(['can_approve_content' => false]);

        $module = Module::factory()->create(['enrollment_mode' => 'auto']);
        $enrollment = ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->actingAs($parent)
            ->post(route('parent.children.enrollments.approve', [$child, $enrollment]))
            ->assertForbidden();

        $this->assertDatabaseHas('module_enrollments', [
            'id' => $enrollment->id,
            'status' => 'pending_parent_approval',
        ]);
    }

    public function test_parent_can_reject_pending_enrollment(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        $module = Module::factory()->create();
        $enrollment = ModuleEnrollment::create([
            'user_id'     => $child->id,
            'module_id'   => $module->id,
            'status'      => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->actingAs($parent)
             ->post(route('parent.children.enrollments.reject', [$child, $enrollment]), [
                 'reason_code' => 'not_ready_for_topic',
             ])
             ->assertRedirect(route('parent.children.show', $child));

        $this->assertDatabaseHas('module_enrollments', [
            'id'     => $enrollment->id,
            'status' => 'rejected',
        ]);
    }

    public function test_parent_without_content_approval_cannot_reject_pending_enrollment(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->update(['can_approve_content' => false]);

        $module = Module::factory()->create();
        $enrollment = ModuleEnrollment::create([
            'user_id' => $child->id,
            'module_id' => $module->id,
            'status' => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        $this->actingAs($parent)
            ->post(route('parent.children.enrollments.reject', [$child, $enrollment]), [
                'reason_code' => 'not_ready_for_topic',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('module_enrollments', [
            'id' => $enrollment->id,
            'status' => 'pending_parent_approval',
        ]);
    }

    public function test_parent_cannot_approve_enrollment_for_another_childs_enrollment(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        // A stranger's child
        $otherChild = User::factory()->create(['email_verified_at' => now()]);
        $otherChild->assignRole('learner');

        $module = Module::factory()->create();
        $enrollment = ModuleEnrollment::create([
            'user_id'     => $otherChild->id,
            'module_id'   => $module->id,
            'status'      => 'pending_parent_approval',
            'enrolled_at' => null,
        ]);

        // Parent tries to approve an enrollment that belongs to a child they don't own
        $this->actingAs($parent)
             ->post(route('parent.children.enrollments.approve', [$otherChild, $enrollment]))
             ->assertForbidden();
    }

    public function test_child_enrollment_is_gated_when_parent_has_content_approval_enabled(): void
    {
        [$parent, $child] = $this->createParentWithChild();

        // Give the child a complete-enough learner profile for the controller
        LearnerProfile::updateOrCreate(
            ['user_id' => $child->id],
            [
                'username'                  => 'testchild',
                'birthdate'                 => now()->subYears(8),
                'requires_parental_consent' => true,
            ]
        );

        $module = Module::factory()->create([
            'enrollment_mode' => 'auto',
            'is_published'    => true,
            'min_age'         => 5,
            'max_age'         => 12,
        ]);

        // Bypass the profile-completion redirect so we hit the enroll() logic
        $this->withoutMiddleware(EnsureProfileCompleted::class)
             ->actingAs($child)
             ->post(route('learner.modules.enroll', $module))
             ->assertRedirect();

        $this->assertDatabaseHas('module_enrollments', [
            'user_id'   => $child->id,
            'module_id' => $module->id,
            'status'    => 'pending_parent_approval',
        ]);
    }
}
