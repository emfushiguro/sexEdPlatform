<?php

namespace Tests\Feature\Support;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\HelpArticleSection;
use App\Models\HelpCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class PublicHelpCenterTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_published_help_and_search_by_title(): void
    {
        $category = HelpCategory::factory()->create(['name' => 'Getting Started', 'slug' => 'getting-started']);
        $matching = HelpArticle::factory()->for($category, 'category')->create([
            'title' => 'Find your dashboard',
            'summary' => 'Learn where to start.',
            'keywords' => ['dashboard'],
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
        ]);
        HelpArticleSection::query()->create([
            'help_article_id' => $matching->id,
            'heading' => 'Open the dashboard',
            'body' => 'Use the dashboard link after signing in.',
            'sort_order' => 0,
        ]);

        $this->get(route('help.index', ['q' => 'dashboard']))
            ->assertOk()
            ->assertSee('Find your dashboard')
            ->assertSee(route('help.show', $matching->slug), false);

        $this->get(route('help.show', $matching->slug))
            ->assertOk()
            ->assertSee('Open the dashboard')
            ->assertSee('Use the dashboard link after signing in.');
    }

    public function test_guest_cannot_view_draft_archived_or_inactive_category_articles(): void
    {
        $active = HelpCategory::factory()->create(['is_active' => true]);
        $inactive = HelpCategory::factory()->create(['is_active' => false]);

        $draft = HelpArticle::factory()->for($active, 'category')->create(['status' => HelpArticleStatus::Draft]);
        $archived = HelpArticle::factory()->for($active, 'category')->create(['status' => HelpArticleStatus::Archived]);
        $hidden = HelpArticle::factory()->for($inactive, 'category')->create([
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
        ]);

        foreach ([$draft, $archived, $hidden] as $article) {
            $this->get(route('help.show', $article->slug))->assertNotFound();
        }
    }

    public function test_authenticated_audience_does_not_receive_admin_only_article(): void
    {
        $category = HelpCategory::factory()->create();
        $public = HelpArticle::factory()->for($category, 'category')->create([
            'audiences' => ['all'],
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
        ]);
        $adminOnly = HelpArticle::factory()->for($category, 'category')->create([
            'audiences' => ['admin'],
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
        ]);
        $learner = \App\Models\User::factory()->create(['role' => 'learner']);

        $this->actingAs($learner)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee($public->title)
            ->assertDontSee($adminOnly->title);

        $this->actingAs($learner)->get(route('help.show', $adminOnly->slug))->assertNotFound();
    }
}
