<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\InstructorProfile;
use App\Models\LearnerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class LearnerInstructorBackgroundPageTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_learner_instructor_background_page_renders_certifications_education_and_professional_sections(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'instructor_bg_' . $learner->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Background profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $instructor->assignRole('instructor');

        InstructorProfile::query()->create([
            'user_id' => $instructor->id,
            'bio' => 'Instructor biography sample',
            'professional_background' => 'Professional instructor details',
            'educational_background' => 'BSEd, Major in Science',
            'certifications' => [
                [
                    'title' => 'Licensed Professional Teacher',
                    'organization' => 'PRC',
                    'completion_date' => '2021-08-01',
                ],
            ],
            'educational_background_entries' => [
                [
                    'school_name' => 'State University',
                    'degree_program' => 'BS Education',
                    'graduation_date' => '2018-04-01',
                ],
            ],
        ]);

        $this->actingAs($learner)
            ->get(route('learner.instructors.show', $instructor))
            ->assertOk()
            ->assertSee('Professional Background', false)
            ->assertSee('Certifications', false)
            ->assertSee('Educational Background', false);
    }
}
