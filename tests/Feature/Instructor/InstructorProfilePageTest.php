<?php

namespace Tests\Feature\Instructor;

use App\Models\InstructorProfile;
use App\Models\User;
use Tests\TestCase;

class InstructorProfilePageTest extends TestCase
{
    public function test_instructor_can_open_profile_page_with_required_sections(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('instructor.profile.show'))
            ->assertOk()
            ->assertSee($instructor->name)
            ->assertSee('Educational Background')
            ->assertSee('Professional Background')
            ->assertSee('Impact Overview');
    }

    public function test_learner_cannot_access_instructor_profile_route(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->actingAs($learner)
            ->get(route('instructor.profile.show'))
            ->assertForbidden();
    }

    public function test_instructor_profile_hides_sensitive_document_credentials_and_formats_education_label(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $instructor->learnerProfile()->create([
            'username' => 'profile-fallback-' . $instructor->id,
            'birthdate' => now()->subYears(24)->toDateString(),
            'avatar_path' => 'avatars/instructor-fallback.png',
        ]);

        InstructorProfile::query()->create([
            'user_id' => $instructor->id,
            'bio' => 'Instructor profile bio for testing.',
            'educational_background' => 'college_graduate',
            'professional_background' => 'Instructor background details.',
            'credentials' => [
                'government_id_path' => 'instructor-applications/id.pdf',
                'clearance_path' => 'instructor-applications/clearance.pdf',
                'Licensed Guidance Counselor',
            ],
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.profile.show'))
            ->assertOk()
            ->assertSee('College Graduate')
            ->assertDontSee('college_graduate')
            ->assertSee('Licensed Guidance Counselor')
            ->assertDontSee('instructor-applications/id.pdf')
            ->assertDontSee('instructor-applications/clearance.pdf')
            ->assertSee('/storage/avatars/instructor-fallback.png', false);
    }
}
