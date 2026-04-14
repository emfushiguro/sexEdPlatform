<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class InstructorModuleReviewSubmissionTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_instructor_can_submit_module_for_admin_review(): void
    {
        $instructor = $this->createInstructor();
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
        ]);

        $this->actingAs($instructor)
            ->post(route('instructor.modules.review.submit', $module))
            ->assertRedirect(route('instructor.modules.show', $module));

        $this->assertDatabaseHas('module_review_requests', [
            'module_id' => $module->id,
            'status' => 'submitted',
            'submitted_by' => $instructor->id,
        ]);
    }

    public function test_rejected_module_can_be_resubmitted(): void
    {
        $instructor = $this->createInstructor();
        $admin = $this->createAdmin();
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
        ]);

        $this->actingAs($instructor)
            ->post(route('instructor.modules.review.submit', $module))
            ->assertRedirect();

        $initialRequest = ModuleReviewRequest::query()->latest('id')->firstOrFail();
        $governanceService = app(\App\Services\ContentGovernanceService::class);
        $governanceService->startReview($initialRequest, $admin);
        $governanceService->rejectReview($initialRequest, $admin, 'Please revise the copy.');

        $this->actingAs($instructor)
            ->post(route('instructor.modules.review.resubmit', $module))
            ->assertRedirect(route('instructor.modules.show', $module));

        $this->assertSame(2, ModuleReviewRequest::query()->where('module_id', $module->id)->count());
        $this->assertDatabaseHas('module_review_requests', [
            'module_id' => $module->id,
            'status' => 'submitted',
        ]);
    }

    public function test_instructor_store_does_not_directly_publish_module_when_governance_is_active(): void
    {
        $instructor = $this->createInstructor();

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store'), [
                'title' => 'Governed Module',
                'description' => 'Needs review before publication',
                'age_bracket' => 'teens',
                'enrollment_mode' => 'auto',
                'is_published' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('modules', [
            'title' => 'Governed Module',
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
        ]);
    }

    private function createInstructor(): User
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $user->assignRole('instructor');

        return $user;
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $user->assignRole('admin');

        return $user;
    }
}
