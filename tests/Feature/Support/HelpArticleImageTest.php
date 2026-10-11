<?php

namespace Tests\Feature\Support;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class HelpArticleImageTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_public_article_image_is_served_inline_with_safe_cache_headers(): void
    {
        Storage::fake('public');
        $article = $this->article(['audiences' => ['all']]);
        $path = 'help/articles/'.$article->id.'/guide.png';
        Storage::disk('public')->put($path, 'png bytes');
        $section = $article->sections()->create(['heading' => 'Open the guide', 'body' => 'Steps', 'image_path' => $path, 'image_alt_text' => 'Screenshot for Open the guide', 'sort_order' => 0]);

        $response = $this->get(route('help.section.image', [$article->slug, $section]));

        $response->assertOk()->assertHeader('Content-Disposition', 'inline')->assertHeader('Cache-Control', 'max-age=3600, public');
    }

    public function test_restricted_article_image_is_private_and_section_cannot_cross_article_boundary(): void
    {
        Storage::fake('public');
        $learner = User::factory()->create(['role' => 'learner']);
        $article = $this->article(['audiences' => ['learner']]);
        $other = $this->article(['audiences' => ['learner']]);
        $path = 'help/articles/'.$article->id.'/guide.png';
        Storage::disk('public')->put($path, 'png bytes');
        $section = $article->sections()->create(['body' => 'Steps', 'image_path' => $path, 'sort_order' => 0]);
        $otherSection = $other->sections()->create(['body' => 'Other', 'image_path' => $path, 'sort_order' => 0]);

        $this->actingAs($learner)->get(route('help.section.image', [$article->slug, $section]))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($learner)->get(route('help.section.image', [$article->slug, $otherSection]))->assertNotFound();
    }

    public function test_admin_preview_uses_the_authenticated_image_endpoint_for_drafts(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $article = $this->article(['status' => HelpArticleStatus::Draft, 'published_at' => null]);
        $path = 'help/articles/'.$article->id.'/guide.png';
        Storage::disk('public')->put($path, 'png bytes');
        $section = $article->sections()->create(['body' => 'Steps', 'image_path' => $path, 'sort_order' => 0]);

        $this->actingAs($admin)->get(route('admin.help.articles.section.image', [$article, $section]))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }

    private function article(array $overrides = []): HelpArticle
    {
        return HelpArticle::factory()->for(HelpCategory::factory()->create(), 'category')->create(array_merge([
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
        ], $overrides));
    }
}
