<?php

namespace Tests\Unit\Services;

use App\Models\InstructorApplication;
use App\Models\InstructorProfile;
use App\Models\User;
use App\Services\InstructorApplicationService;
use Tests\TestCase;

class InstructorProfileSeedingTest extends TestCase
{
    public function test_approval_seeds_educational_and_professional_background_from_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $application = InstructorApplication::create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'Bachelor of Science in Education',
            'bio' => 'Classroom and training facilitator for 8 years.',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'teaching_credential_path' => 'instructor-applications/teaching.pdf',
        ]);

        $this->actingAs($admin);

        app(InstructorApplicationService::class)->approve($application);

        $profile = InstructorProfile::where('user_id', $learner->id)->first();

        $this->assertNotNull($profile);
        $this->assertSame('Bachelor of Science in Education', $profile->educational_background);
        $this->assertSame('Classroom and training facilitator for 8 years.', $profile->professional_background);
    }
}
