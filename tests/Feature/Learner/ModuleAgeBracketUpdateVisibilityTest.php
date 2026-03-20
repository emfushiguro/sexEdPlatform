<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAgeBracketUpdateVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_updated_module_age_bracket_is_reflected_in_learner_module_list(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Body Awareness 101',
            'description' => 'Initial description',
            'is_published' => true,
            'enrollment_mode' => 'auto',
            'min_age' => 5,
            'max_age' => 12,
        ]);

        $this->actingAs($instructor)
            ->put(route('instructor.modules.update', $module), [
                'title' => $module->title,
                'description' => $module->description,
                'age_bracket' => 'teens',
                'enrollment_mode' => 'auto',
                'is_published' => 1,
            ])
            ->assertRedirect(route('instructor.modules.index'));

        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'min_age' => 13,
            'max_age' => 17,
        ]);

        $teenLearner = $this->createLearnerWithBirthdate(now()->subYears(15)->toDateString());
        $kidLearner = $this->createLearnerWithBirthdate(now()->subYears(10)->toDateString());

        $this->actingAs($teenLearner)
            ->get(route('learner.modules.index'))
            ->assertOk()
            ->assertSee('Body Awareness 101', false);

        $this->actingAs($kidLearner)
            ->get(route('learner.modules.index'))
            ->assertOk()
            ->assertDontSee('Body Awareness 101', false);
    }

    private function createLearnerWithBirthdate(string $birthdate): User
    {
        $learner = User::factory()->create([
            'role' => 'learner',
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'learner_' . $learner->id,
            'birthdate' => $birthdate,
            'age_range' => 'adult_18_plus',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Bio',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        return $learner;
    }
}
