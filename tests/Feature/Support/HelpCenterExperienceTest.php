<?php

namespace Tests\Feature\Support;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\HelpArticleSection;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class HelpCenterExperienceTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_landing_shows_role_recommendations_visible_guide_counts_and_category_icons(): void
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'account_type' => 'learner-adult',
            'age' => 25,
            'birthdate' => now()->subYears(25),
        ]);
        $user->assignRole('learner');

        $category = HelpCategory::factory()->create([
            'name' => 'Learning and Modules',
            'slug' => 'learning-and-modules',
            'icon_key' => 'book',
            'audiences' => ['learner'],
        ]);
        HelpArticle::factory()->for($category, 'category')->create([
            'title' => 'Start a learning module',
            'audiences' => ['learner'],
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
        ]);
        HelpArticle::factory()->for($category, 'category')->create([
            'title' => 'Hidden draft',
            'audiences' => ['learner'],
            'status' => HelpArticleStatus::Draft,
            'published_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Recommended for learners')
            ->assertSee('Start a learning module')
            ->assertSee('1 guide')
            ->assertSee('data-help-category-icon="book"', false)
            ->assertDontSee('Hidden draft');
    }

    public function test_article_has_contents_metadata_and_contextual_feedback_link(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $user->assignRole('learner');
        $category = HelpCategory::factory()->create(['audiences' => ['all']]);
        $article = HelpArticle::factory()->for($category, 'category')->create([
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
            'audiences' => ['all'],
        ]);

        foreach (['Open dashboard', 'Choose a module'] as $index => $heading) {
            HelpArticleSection::query()->create([
                'help_article_id' => $article->id,
                'heading' => $heading,
                'body' => str_repeat('Helpful guide text ', 40),
                'sort_order' => $index,
            ]);
        }

        $this->actingAs($user)->get(route('help.show', $article->slug))
            ->assertOk()
            ->assertSee('On this page')
            ->assertSee('Last updated')
            ->assertSee('min read')
            ->assertSee('href="#section-1-open-dashboard"', false)
            ->assertSee(route('feedback.create', ['type' => 'help_content_issue']))
            ->assertDontSee('affected_path');
    }

    public function test_authenticated_help_center_contains_all_personal_support_destinations(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'account_type' => User::ACCOUNT_TYPE_INSTRUCTOR,
            'status' => User::STATUS_ACTIVE,
            'age' => null,
            'birthdate' => null,
        ]);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('data-support-hub', false)
            ->assertSee('Submit a Ticket')
            ->assertSee('My Tickets')
            ->assertSee('Share Your Experience')
            ->assertSee('My Testimonials')
            ->assertSee(route('feedback.create'), false)
            ->assertSee(route('feedback.index'), false)
            ->assertSee(route('testimonials.create'), false)
            ->assertSee(route('testimonials.index'), false);
    }
}
