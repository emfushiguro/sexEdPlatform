<?php

namespace Tests\Feature\Instructor;

use App\Models\InstructorModerationProfile;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class InstructorRestrictionUiStateTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_index_shows_restriction_banner_and_disabled_create_action(): void
    {
        $instructor = $this->createRestrictedInstructor();

        $this->actingAs($instructor)
            ->get(route('instructor.modules.index'))
            ->assertOk()
            ->assertSee('Module actions are temporarily restricted', false)
            ->assertSee('data-testid="create-module-disabled"', false)
            ->assertSee('Restriction ends', false);
    }

    public function test_edit_page_shows_restriction_notice_and_disables_submit(): void
    {
        $instructor = $this->createRestrictedInstructor();
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'draft',
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.modules.edit', $module))
            ->assertOk()
            ->assertSee('Module actions are temporarily restricted', false)
            ->assertSee('data-testid="restricted-edit-submit"', false)
            ->assertSee('disabled', false);
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
            'warning_count' => 3,
            'current_restriction_status' => 'restricted',
            'restriction_starts_at' => now()->subDay(),
            'restriction_ends_at' => now()->addDays(10),
            'last_violation_at' => now()->subDay(),
            'escalation_level' => 3,
        ]);

        return $instructor;
    }
}
