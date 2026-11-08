<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\User;
use App\Services\Admin\RoleSyncService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicantAccountClassificationTest extends TestCase
{
    public function test_role_sync_assigns_applicant_in_both_role_systems(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        app(RoleSyncService::class)->assignPrimaryRole($user, 'applicant');

        $this->assertSame('applicant', $user->fresh()->role);
        $this->assertTrue($user->fresh()->hasRole('applicant'));
        $this->assertTrue($user->fresh()->isInstructorApplicant());
    }

    public function test_applicant_role_has_no_permissions_and_reviewer_permissions_are_admin_only(): void
    {
        $applicant = Role::findByName('applicant', 'web');
        $admin = Role::findByName('admin', 'web');

        $this->assertCount(0, $applicant->permissions);
        $this->assertTrue($admin->hasPermissionTo('review instructor identities'));
        $this->assertTrue($admin->hasPermissionTo('review instructor applications'));
        $this->assertFalse($applicant->hasPermissionTo('review instructor identities'));
        $this->assertFalse($applicant->hasPermissionTo('review instructor applications'));
    }

    public function test_applicant_is_not_a_learner_instructor_or_admin(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);
        $user->assignRole('applicant');

        $this->assertTrue($user->isInstructorApplicant());
        $this->assertFalse($user->isLearner());
        $this->assertFalse($user->isInstructor());
        $this->assertFalse($user->isAdmin());
    }
}
