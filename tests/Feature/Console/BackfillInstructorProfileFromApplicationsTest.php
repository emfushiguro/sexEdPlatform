<?php

namespace Tests\Feature\Console;

use App\Models\InstructorApplication;
use App\Models\InstructorProfile;
use App\Models\User;
use Tests\TestCase;

class BackfillInstructorProfileFromApplicationsTest extends TestCase
{
    public function test_command_backfills_null_fields_from_latest_approved_application_only(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $user->assignRole('instructor');

        InstructorProfile::create([
            'user_id' => $user->id,
            'bio' => 'Profile bio',
            'educational_background' => null,
            'professional_background' => null,
        ]);

        InstructorApplication::create([
            'user_id' => $user->id,
            'status' => 'approved',
            'educational_background' => 'Older Approved Education',
            'bio' => 'Older Approved Bio',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'teaching_credential_path' => 'instructor-applications/teaching.pdf',
            'approved_at' => now()->subDays(2),
        ]);

        InstructorApplication::create([
            'user_id' => $user->id,
            'status' => 'approved',
            'educational_background' => 'Latest Approved Education',
            'bio' => 'Latest Approved Bio',
            'government_id_path' => 'instructor-applications/id-latest.pdf',
            'clearance_path' => 'instructor-applications/clearance-latest.pdf',
            'teaching_credential_path' => 'instructor-applications/teaching-latest.pdf',
            'approved_at' => now()->subDay(),
        ]);

        InstructorApplication::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'educational_background' => 'Pending Education',
            'bio' => 'Pending Bio',
            'government_id_path' => 'instructor-applications/id-pending.pdf',
            'clearance_path' => 'instructor-applications/clearance-pending.pdf',
            'teaching_credential_path' => 'instructor-applications/teaching-pending.pdf',
            'approved_at' => null,
        ]);

        $this->artisan('instructor-profile:backfill-from-applications')
            ->assertExitCode(0);

        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $user->id,
            'educational_background' => 'Latest Approved Education',
            'professional_background' => 'Latest Approved Bio',
        ]);

        $this->artisan('instructor-profile:backfill-from-applications')
            ->assertExitCode(0);

        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $user->id,
            'educational_background' => 'Latest Approved Education',
            'professional_background' => 'Latest Approved Bio',
        ]);
    }
}
