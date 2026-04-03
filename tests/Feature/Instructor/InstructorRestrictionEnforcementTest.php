<?php

namespace Tests\Feature\Instructor;

use App\Models\InstructorModerationProfile;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class InstructorRestrictionEnforcementTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_restricted_instructor_cannot_access_module_create_page(): void
    {
        $instructor = $this->createRestrictedInstructor();

        $this->actingAs($instructor)
            ->get(route('instructor.modules.create'))
            ->assertRedirect(route('instructor.modules.index'))
            ->assertSessionHas('error');
    }

    public function test_restricted_instructor_cannot_store_module(): void
    {
        $instructor = $this->createRestrictedInstructor();

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store'), [
                'title' => 'Restricted Module',
                'description' => 'Module should not be created while restricted.',
                'age_bracket' => 'teens',
                'enrollment_mode' => 'auto',
                'access_type' => 'free',
                'price_currency' => 'PHP',
            ])
            ->assertRedirect(route('instructor.modules.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('modules', [
            'created_by' => $instructor->id,
            'title' => 'Restricted Module',
        ]);
    }

    public function test_restricted_instructor_cannot_submit_or_resubmit_for_review(): void
    {
        $instructor = $this->createRestrictedInstructor();
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'draft',
        ]);

        $this->actingAs($instructor)
            ->post(route('instructor.modules.review.submit', $module))
            ->assertRedirect(route('instructor.modules.show', $module))
            ->assertSessionHas('error');

        $this->actingAs($instructor)
            ->post(route('instructor.modules.review.resubmit', $module))
            ->assertRedirect(route('instructor.modules.show', $module))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('module_review_requests', [
            'module_id' => $module->id,
        ]);
    }

    private function createRestrictedInstructor(): User
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        InstructorModerationProfile::query()->create([
            'user_id' => $instructor->id,
            'warning_count' => 2,
            'current_restriction_status' => 'restricted',
            'restriction_starts_at' => now()->subDay(),
            'restriction_ends_at' => now()->addDays(2),
            'last_violation_at' => now()->subDay(),
            'escalation_level' => 2,
        ]);

        return $instructor;
    }
}
