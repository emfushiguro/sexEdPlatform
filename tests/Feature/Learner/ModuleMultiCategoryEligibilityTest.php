<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\User;
use Tests\TestCase;

class ModuleMultiCategoryEligibilityTest extends TestCase
{
    public function test_legacy_mixed_kids_content_is_only_visible_to_its_active_audience(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $legacyMixed = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Family Boundaries',
            'is_published' => true,
            'current_review_status' => null,
            'min_age' => 10,
            'max_age' => 17,
        ]);
        $legacyMixed->learnerCategories()->createMany([
            ['category' => 'kids'],
            ['category' => 'teens'],
        ]);

        $teenOnly = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Teen Boundaries',
            'is_published' => true,
            'current_review_status' => null,
            'min_age' => 10,
            'max_age' => 17,
        ]);
        $teenOnly->syncLearnerCategories(['teens']);

        $kidney = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Kidney Health',
            'is_published' => true,
            'current_review_status' => null,
            'min_age' => 10,
            'max_age' => 17,
        ]);
        $kidney->syncLearnerCategories(['teens']);

        $ineligible = $this->learnerBorn(now()->subYears(9)->toDateString());
        $teen = $this->learnerBorn(now()->subYears(15)->toDateString());
        $adult = $this->learnerBorn(now()->subYears(22)->toDateString());

        $this->actingAs($ineligible)
            ->get(route('learner.modules.index'))
            ->assertOk()
            ->assertDontSee('Family Boundaries', false)
            ->assertDontSee('Teen Boundaries', false)
            ->assertDontSee('Kidney Health', false);

        $this->actingAs($teen)
            ->get(route('learner.modules.index'))
            ->assertOk()
            ->assertSee('Family Boundaries', false)
            ->assertSee('Teen Boundaries', false)
            ->assertSee('Kidney Health', false);

        $this->actingAs($adult)
            ->get(route('learner.modules.index'))
            ->assertOk()
            ->assertDontSee('Family Boundaries', false)
            ->assertDontSee('Teen Boundaries', false)
            ->assertDontSee('Kidney Health', false);
    }

    private function learnerBorn(string $birthdate): User
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'eligibility_'.$learner->id,
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
