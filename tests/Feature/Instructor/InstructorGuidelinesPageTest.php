<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use Tests\TestCase;

class InstructorGuidelinesPageTest extends TestCase
{
    public function test_instructor_can_open_the_guidelines_page_with_all_reference_sections(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $response = $this->actingAs($instructor)
            ->get('/instructor/guidelines')
            ->assertOk()
            ->assertViewIs('instructor.guidelines');

        foreach ([
            'overview',
            'content-principles',
            'learner-category-guidelines',
            'kids-guidelines',
            'teens-guidelines',
            'adults-guidelines',
            'sensitive-topics',
            'module-guidelines',
            'interactive-guidelines',
            'media-guidelines',
            'video-guidelines',
            'accessibility-guidelines',
            'prohibited-content',
            'sources-references',
            'before-you-publish',
        ] as $anchor) {
            $response->assertSee('id="'.$anchor.'"', false);
        }

        $response
            ->assertSeeText('Instructor Guidelines')
            ->assertSeeText('Learn → Practice → Apply → Assess')
            ->assertSeeText('Sexualization of minors')
            ->assertSeeText('Before You Publish');
    }

    public function test_guidelines_page_is_discoverable_from_the_instructor_sidebar(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get('/instructor/guidelines')
            ->assertOk()
            ->assertSee('/instructor/guidelines', false)
            ->assertSeeText('Guidelines');
    }

    public function test_guest_cannot_access_the_guidelines_page(): void
    {
        $this->get('/instructor/guidelines')->assertRedirect();
    }

    public function test_learner_cannot_access_the_guidelines_page(): void
    {
        $learner = User::factory()->create();
        $learner->assignRole('learner');

        $this->actingAs($learner)
            ->get('/instructor/guidelines')
            ->assertForbidden();
    }
}
