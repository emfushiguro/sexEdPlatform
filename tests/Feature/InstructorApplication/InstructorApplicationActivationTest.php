<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorApplication;
use App\Models\InstructorIdentityVerification;
use App\Models\User;
use App\Services\Admin\RoleSyncService;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InstructorApplicationActivationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['instructor_application.enabled' => true, 'instructor_application.identity_enforced_from' => now()->subDay()->toIso8601String()]);
        Notification::fake();
        Permission::findOrCreate('review instructor applications', 'web');
    }

    public function test_only_final_approval_activates_a_direct_applicant(): void
    {
        [$applicant, $application] = $this->pendingDirectApplication();
        $admin = $this->admin();

        $this->assertFalse($applicant->fresh()->isInstructor());
        $this->actingAs($admin)
            ->post(route('admin.instructor-applications.approve', $application), [
                'admin_message' => '<p>Approved after final review.</p>',
                'review_application_id' => $application->id,
            ])
            ->assertRedirect();

        $applicant = $applicant->fresh();
        $this->assertSame('instructor', $applicant->role);
        $this->assertTrue($applicant->isInstructor());
        $this->assertNotNull($applicant->instructorProfile);
        $this->assertNull($applicant->learnerProfile);
        $this->assertDatabaseCount('instructor_application_reviews', 1);
    }

    public function test_persona_or_stage_one_without_access_eligible_identity_cannot_activate(): void
    {
        $applicant = $this->applicant();
        $application = $this->application($applicant);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.instructor-applications.approve', $application), [
                'admin_message' => '<p>Attempted approval.</p>',
                'review_application_id' => $application->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('application');

        $this->assertSame('applicant', $applicant->fresh()->role);
        $this->assertDatabaseHas('instructor_applications', ['id' => $application->id, 'status' => 'pending']);
        $this->assertDatabaseCount('instructor_application_reviews', 0);
    }

    public function test_stale_second_decision_does_not_create_a_second_review(): void
    {
        [$applicant, $application] = $this->pendingDirectApplication();
        $admin = $this->admin();
        $payload = ['admin_message' => '<p>Final review.</p>', 'review_application_id' => $application->id];

        $this->actingAs($admin)->post(route('admin.instructor-applications.approve', $application), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('admin.instructor-applications.approve', $application), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('application');

        $this->assertDatabaseCount('instructor_application_reviews', 1);
    }

    private function pendingDirectApplication(): array
    {
        $applicant = $this->applicant();
        InstructorIdentityVerification::create([
            'user_id' => $applicant->id,
            'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
            'status' => InstructorIdentityVerification::STATUS_APPROVED,
            'submission_round' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        return [$applicant, $this->application($applicant)];
    }

    private function application(User $user): InstructorApplication
    {
        return InstructorApplication::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'submitted_at' => now(),
            'document_disk' => 'local',
            'clearance_path' => 'instructor-applications/'.$user->id.'/clearance.pdf',
            'cv_resume_path' => 'instructor-applications/'.$user->id.'/cv.pdf',
            'bio' => 'Synthetic application',
            'teaching_credential_path' => 'instructor-applications/'.$user->id.'/teaching.pdf',
        ]);
    }

    private function applicant(): User
    {
        $user = User::factory()->create([
            'role' => User::ACCOUNT_TYPE_APPLICANT,
            'account_type' => User::ACCOUNT_TYPE_APPLICANT,
            'status' => User::STATUS_ACTIVE,
            'birthdate' => now()->subYears(24)->toDateString(),
            'email_verified_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, User::ACCOUNT_TYPE_APPLICANT);

        return $user->fresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        app(RoleSyncService::class)->assignPrimaryRole($admin, 'admin');
        $admin->givePermissionTo('review instructor applications');

        return $admin->fresh();
    }
}
