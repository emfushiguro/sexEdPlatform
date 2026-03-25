<?php

namespace Tests\Unit;

use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\InstructorApplicationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorApplicationServiceTest extends TestCase
{
    public function test_submit_application_stores_files_and_record(): void
    {
        Storage::fake('public');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $service = app(InstructorApplicationService::class);
        $application = $service->submitApplication($learner, [
            'government_id' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
            'clearance' => UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf'),
            'bio' => str_repeat('C', 120),
            'professional_license' => UploadedFile::fake()->create('license.pdf', 200, 'application/pdf'),
        ]);

        $this->assertInstanceOf(InstructorApplication::class, $application);
        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'pending',
        ]);
        Storage::disk('public')->assertExists($application->government_id_path);
    }

    public function test_approve_and_reject_methods_update_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $application = InstructorApplication::create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'bio' => str_repeat('D', 120),
            'teaching_credential_path' => 'instructor-applications/teach.pdf',
        ]);

        $service = app(InstructorApplicationService::class);
        $service->approve($application);

        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);

        $second = InstructorApplication::create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'government_id_path' => 'instructor-applications/id2.pdf',
            'clearance_path' => 'instructor-applications/clearance2.pdf',
            'bio' => str_repeat('E', 120),
            'professional_license_path' => 'instructor-applications/license.pdf',
        ]);

        $service->reject($second, 'Please provide a clearer file scan.');
        $this->assertDatabaseHas('instructor_applications', [
            'id' => $second->id,
            'status' => 'rejected',
        ]);
    }
}
