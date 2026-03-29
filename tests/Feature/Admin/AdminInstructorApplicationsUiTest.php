<?php

namespace Tests\Feature\Admin;

use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminInstructorApplicationsUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_instructor_applications_index_uses_enhanced_table_structure(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $application = $this->createPendingApplication();

        $this->actingAs($admin)
            ->get(route('admin.instructor-applications.index'))
            ->assertOk()
            ->assertSee($application->user->name, false)
            ->assertSee('Username', false)
            ->assertSee('Location', false)
            ->assertSee('Educational Background', false)
            ->assertSee('Professional Background', false)
            ->assertSee('Date Applied', false)
            ->assertSee('Status', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false);
    }

    public function test_instructor_application_show_displays_structured_reject_inputs(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $application = $this->createPendingApplication();

        $this->actingAs($admin)
            ->get(route('admin.instructor-applications.show', $application))
            ->assertOk()
            ->assertSee('name="rejection_reason_code"', false)
            ->assertSee('name="rejection_reason_note"', false)
            ->assertSee('Reject Instructor Application', false);
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
        ]);

        return InstructorApplication::query()->create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'Bachelor of Secondary Education',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'bio' => 'Professional educator focused on adolescent development and evidence-based facilitation.',
            'teaching_credential_path' => 'instructor-applications/teaching.pdf',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
    }
}
