<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\InstructorApplication;
use App\Models\InstructorIdentityVerification;
use App\Models\LearnerIdentityVerification;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorApplicationSubmissionTest extends TestCase
{
    use WithFaker;

    public function test_repeated_learner_submission_opens_the_existing_application(): void
    {
        config(['instructor_application.identity_enforced_from' => null]);
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $learner->instructorApplications()->create(['status' => 'pending', 'bio' => 'Existing application']);

        $this->withoutMiddleware(EnsureProfileCompleted::class)->actingAs($learner)
            ->post(route('learner.instructor.apply.submit'), [])
            ->assertRedirect(route('learner.instructor.submitted'));

        $this->assertSame(1, $learner->instructorApplications()->count());
    }

    public function test_guardian_registration_submission_explains_the_restriction(): void
    {
        $learner = User::factory()->create(['role' => 'learner', 'is_parent_registration' => true]);
        $learner->assignRole('learner');

        $this->withoutMiddleware(EnsureProfileCompleted::class)->actingAs($learner)
            ->post(route('learner.instructor.apply.submit'), [])
            ->assertRedirect(route('learner.dashboard'))
            ->assertSessionHas('error', 'Guardian-registration accounts cannot apply as instructors in this workflow.');

        $this->assertSame(0, $learner->instructorApplications()->count());

        $this->get(route('learner.instructor.apply'))
            ->assertRedirect(route('learner.dashboard'))
            ->assertSessionHas('error', 'Guardian-registration accounts cannot apply as instructors in this workflow.');
    }

    public function test_identity_enforced_learner_application_reuses_approved_learner_identity_before_submission(): void
    {
        config([
            'instructor_application.enabled' => true,
            'instructor_application.identity_enforced_from' => now()->subDay()->toIso8601String(),
        ]);

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'birthdate' => now()->subYears(24)->toDateString(),
            'email_verified_at' => now(),
        ]);
        $learner->assignRole('learner');
        $learnerIdentity = LearnerIdentityVerification::create([
            'user_id' => $learner->id,
            'pathway' => 'adult',
            'status' => 'approved',
            'submission_round' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        Storage::fake('local');
        Notification::fake();
        $this->withoutMiddleware(EnsureProfileCompleted::class)->actingAs($learner);

        $this->post(route('learner.instructor.apply.submit'), [
            'clearance' => UploadedFile::fake()->create('clearance.pdf', 20, 'application/pdf'),
            'cv_resume' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
            'educational_background' => 'college_graduate',
            'teaching_credential' => UploadedFile::fake()->create('teaching.pdf', 20, 'application/pdf'),
            'confirmation' => '1',
        ])->assertRedirect(route('learner.instructor.submitted'));

        $this->assertDatabaseHas('instructor_identity_verifications', [
            'user_id' => $learner->id,
            'learner_identity_verification_id' => $learnerIdentity->id,
            'source' => InstructorIdentityVerification::SOURCE_LEARNER_IDENTITY,
            'status' => InstructorIdentityVerification::STATUS_APPROVED,
        ]);
    }

    public function test_learner_can_submit_valid_application(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Notification::fake();

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $response = $this->withoutMiddleware(EnsureProfileCompleted::class)
            ->actingAs($learner)
            ->post(route('learner.instructor.apply.submit'), [
                'government_id' => UploadedFile::fake()->create('id.webp', 200, 'image/webp'),
                'clearance' => UploadedFile::fake()->create('clearance.webp', 200, 'image/webp'),
                'cv_resume' => UploadedFile::fake()->create('cv_resume.pdf', 200, 'application/pdf'),
                'educational_background' => 'college_graduate',
                'bio' => str_repeat('A', 120),
                'teaching_credential' => UploadedFile::fake()->create('teaching.webp', 200, 'image/webp'),
                'confirmation' => '1',
            ]);

        $response->assertRedirect(route('learner.instructor.submitted'));
        $this->assertDatabaseHas('instructor_applications', [
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $application = InstructorApplication::where('user_id', $learner->id)->firstOrFail();
        $this->assertTrue(Storage::disk('local')->exists($application->government_id_path));
        $this->assertTrue(Storage::disk('local')->exists($application->clearance_path));
        $this->assertTrue(Storage::disk('local')->exists($application->cv_resume_path));
    }

    public function test_application_requires_at_least_one_tier2_document(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $response = $this->withoutMiddleware(EnsureProfileCompleted::class)
            ->actingAs($learner)
            ->post(route('learner.instructor.apply.submit'), [
                'government_id' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
                'clearance' => UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf'),
                'cv_resume' => UploadedFile::fake()->create('cv_resume.pdf', 200, 'application/pdf'),
                'educational_background' => 'college_graduate',
                'bio' => str_repeat('A', 120),
                'confirmation' => '1',
            ]);

        $response->assertSessionHasErrors('tier2');
    }

    public function test_learner_parent_can_submit_valid_application(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Notification::fake();

        /** @var User $learnerParent */
        $learnerParent = User::factory()->create(['role' => 'learner']);
        $learnerParent->assignRole('learner');

        /** @var User $child */
        $child = User::factory()->create(['role' => 'learner']);
        $child->assignRole('learner');

        ParentChildAccount::create([
            'parent_user_id' => $learnerParent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => true,
            'relationship_verified_at' => now(),
        ]);

        $response = $this->withoutMiddleware(EnsureProfileCompleted::class)
            ->actingAs($learnerParent)
            ->post(route('learner.instructor.apply.submit'), [
                'government_id' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
                'clearance' => UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf'),
                'cv_resume' => UploadedFile::fake()->create('cv_resume.pdf', 200, 'application/pdf'),
                'educational_background' => 'college_graduate',
                'bio' => str_repeat('B', 120),
                'sexed_certificate' => UploadedFile::fake()->create('sexed.pdf', 200, 'application/pdf'),
                'confirmation' => '1',
            ]);

        $response->assertRedirect(route('learner.instructor.submitted'));
        $this->assertDatabaseHas('instructor_applications', [
            'user_id' => $learnerParent->id,
            'status' => 'pending',
        ]);
    }
}
