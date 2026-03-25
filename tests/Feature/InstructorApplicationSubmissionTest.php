<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\InstructorApplication;
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

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $response = $this->withoutMiddleware(EnsureProfileCompleted::class)
            ->actingAs($learner)
            ->post(route('learner.instructor.apply.submit'), [
                'government_id' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
                'clearance' => UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf'),
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
        Storage::disk('public')->assertExists($application->government_id_path);
        Storage::disk('public')->assertExists($application->clearance_path);
    }

    public function test_application_requires_at_least_one_tier2_document(): void
    {
        Storage::fake('public');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $response = $this->withoutMiddleware(EnsureProfileCompleted::class)
            ->actingAs($learner)
            ->post(route('learner.instructor.apply.submit'), [
                'government_id' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
                'clearance' => UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf'),
                'educational_background' => 'college_graduate',
                'bio' => str_repeat('A', 120),
                'confirmation' => '1',
            ]);

        $response->assertSessionHasErrors('tier2');
    }
}
