<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\InstructorApplication;
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

    public function test_learner_can_submit_valid_application(): void
    {
        Storage::fake('public');
        Notification::fake();

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
                'teaching_credential' => UploadedFile::fake()->create('teaching.pdf', 200, 'application/pdf'),
                'confirmation' => '1',
            ]);

        $response->assertRedirect(route('learner.instructor.submitted'));
        $this->assertDatabaseHas('instructor_applications', [
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $application = InstructorApplication::where('user_id', $learner->id)->firstOrFail();
        $this->assertTrue(Storage::disk('public')->exists($application->government_id_path));
        $this->assertTrue(Storage::disk('public')->exists($application->clearance_path));
        $this->assertTrue(Storage::disk('public')->exists($application->cv_resume_path));
    }

    public function test_application_requires_at_least_one_tier2_document(): void
    {
        Storage::fake('public');

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
