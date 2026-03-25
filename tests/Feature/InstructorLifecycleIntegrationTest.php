<?php

namespace Tests\Feature;

use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\InstructorApplicationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorLifecycleIntegrationTest extends TestCase
{
    public function test_complete_instructor_lifecycle_from_learner_to_instructor(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $service = app(InstructorApplicationService::class);

        $application = $service->submitApplication($learner, [
            'government_id' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
            'clearance' => UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf'),
            'bio' => str_repeat('F', 120),
            'sexed_certificate' => UploadedFile::fake()->create('sexed.pdf', 200, 'application/pdf'),
        ]);

        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        $service->approve($application->fresh());

        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'role' => 'instructor',
        ]);
        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $learner->id,
        ]);
        $this->assertDatabaseHas('role_transitions', [
            'user_id' => $learner->id,
            'from_role' => 'learner',
            'to_role' => 'instructor',
        ]);

        $approved = InstructorApplication::findOrFail($application->id);
        $this->assertSame('approved', $approved->status);
    }
}
