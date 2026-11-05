<?php

namespace Tests\Feature\Admin;

use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminInstructorApplicationsUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_instructor_applications_index_redirects_to_the_centralized_queue(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');
        $admin->givePermissionTo('review instructor applications');

        $application = $this->createPendingApplication();

        $this->actingAs($admin)
            ->get(route('admin.instructor-applications.index'))
            ->assertRedirect(route('admin.instructor-applications.identities.index'));
    }

    public function test_standalone_instructor_application_detail_renders_stage_two_review(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');
        $admin->givePermissionTo('review instructor applications');

        $application = $this->createPendingApplication();

        $this->actingAs($admin)
            ->get(route('admin.instructor-applications.show', $application))
            ->assertOk()
            ->assertSee('Stage Two application', false)
            ->assertSee('Submitted documents', false)
            ->assertSee('NBI / Police clearance', false)
            ->assertSee('name="rejection_reason_code"', false)
            ->assertSee('name="admin_message"', false);
    }

    private function createPendingApplication(): InstructorApplication
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        $learner->learnerProfile()->create([
            'username' => 'ui-check-learner',
            'birthdate' => now()->subYears(20)->toDateString(),
            'city_code' => null,
            'barangay' => 'Barangay Test',
            'barangay_code' => null,
            'avatar_path' => 'avatars/applicant-avatar.png',
        ]);

        return InstructorApplication::query()->create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'Bachelor of Secondary Education',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'cv_resume_path' => 'instructor-applications/cv_resume.pdf',
            'bio' => 'Professional educator focused on adolescent development and evidence-based facilitation.',
            'teaching_credential_path' => 'instructor-applications/teaching.pdf',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
    }
}
