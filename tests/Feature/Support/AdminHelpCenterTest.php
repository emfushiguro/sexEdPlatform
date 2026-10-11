<?php

namespace Tests\Feature\Support;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class AdminHelpCenterTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_open_help_administration(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($learner)->get(route('admin.help.categories.index'))->assertForbidden();
    }

    public function test_admin_can_create_category_article_preview_publish_and_archive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.help.categories.store'), [
                'name' => 'Admin Guides',
                'slug' => 'admin-guides',
                'description' => 'Guides for administrators.',
                'icon_key' => 'tools',
                'audiences' => ['admin'],
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.help.categories.index'));

        $category = HelpCategory::query()->where('slug', 'admin-guides')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.help.articles.store'), [
                'help_category_id' => $category->id,
                'title' => 'Review platform feedback',
                'slug' => 'review-platform-feedback',
                'summary' => 'Review feedback from users.',
                'keywords' => ['feedback', 'admin'],
                'audiences' => ['admin'],
                'status' => 'draft',
                'sort_order' => 1,
                'sections' => [
                    ['heading' => 'Open the inbox', 'body' => 'Use the feedback inbox from Support.'],
                ],
            ])
            ->assertRedirect(route('admin.help.articles.index'));

        $article = HelpArticle::query()->where('slug', 'review-platform-feedback')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.help.articles.preview', $article))->assertOk()->assertSee('Review platform feedback');
        $this->actingAs($admin)->post(route('admin.help.articles.publish', $article))->assertRedirect();
        $this->assertDatabaseHas('help_articles', ['id' => $article->id, 'status' => HelpArticleStatus::Published->value]);
        $this->actingAs($admin)->post(route('admin.help.articles.archive', $article))->assertRedirect();
        $this->assertDatabaseHas('help_articles', ['id' => $article->id, 'status' => HelpArticleStatus::Archived->value]);
    }

    public function test_admin_help_forms_generate_slugs_use_audience_checkboxes_and_preview_images(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $category = HelpCategory::factory()->create(['audiences' => ['all']]);
        $article = HelpArticle::factory()->create(['help_category_id' => $category->id, 'audiences' => ['learner']]);

        $articleForm = $this->actingAs($admin)
            ->get(route('admin.help.articles.edit', $article))
            ->assertOk()
            ->assertDontSee('name="slug"', false)
            ->assertDontSee('name="keywords_text"', false)
            ->assertSee('data-help-section-image-preview', false)
            ->assertSee('data-help-section-image-preview-image', false);

        $articleXpath = $this->dom($articleForm->getContent());
        $this->assertSame(0, $articleXpath->query('//select[@name="audiences[]"][@multiple]')->length);
        $this->assertGreaterThan(0, $articleXpath->query('//input[@name="audiences[]"][@type="checkbox"]')->length);

        $categoryForm = $this->get(route('admin.help.categories.edit', $category))
            ->assertOk()
            ->assertDontSee('name="slug"', false);
        $categoryXpath = $this->dom($categoryForm->getContent());
        $this->assertSame(0, $categoryXpath->query('//select[@name="audiences[]"][@multiple]')->length);
        $this->assertGreaterThan(0, $categoryXpath->query('//input[@name="audiences[]"][@type="checkbox"]')->length);
    }

    public function test_admin_can_create_help_records_without_manual_slugs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.help.categories.store'), [
                'name' => 'Generated Category',
                'description' => 'Generated category description.',
                'icon_key' => 'tools',
                'audiences' => ['all'],
                'sort_order' => 0,
                'is_active' => true,
            ])
            ->assertRedirect();

        $category = HelpCategory::query()->where('name', 'Generated Category')->firstOrFail();
        $this->assertSame('generated-category', $category->slug);

        $this->post(route('admin.help.articles.store'), [
            'help_category_id' => $category->id,
            'title' => 'Generated Article',
            'summary' => 'Generated article summary.',
            'audiences' => ['all'],
            'status' => 'draft',
            'sort_order' => 0,
            'sections' => [['body' => 'Generated article body.']],
        ])->assertRedirect();

        $article = HelpArticle::query()->where('title', 'Generated Article')->firstOrFail();
        $this->assertSame('generated-article', $article->slug);
    }

    public function test_help_lists_use_icon_only_actions_and_preview_exposes_workflow_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $category = HelpCategory::factory()->create(['audiences' => ['all']]);
        $article = HelpArticle::factory()->create([
            'help_category_id' => $category->id,
            'status' => HelpArticleStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.help.articles.index'))
            ->assertOk()
            ->assertSee('data-help-article-action="preview"', false)
            ->assertSee('aria-label="Preview help article"', false)
            ->assertDontSee('>Preview</a>', false)
            ->assertDontSee('>Edit</a>', false);

        $this->actingAs($admin)
            ->get(route('admin.help.categories.index'))
            ->assertOk()
            ->assertSee('data-help-category-action="edit"', false)
            ->assertSee('aria-label="Edit help category"', false)
            ->assertDontSee('>Edit</a>', false);

        $this->actingAs($admin)
            ->get(route('admin.help.articles.preview', $article))
            ->assertOk()
            ->assertSee('data-help-article-action="edit"', false)
            ->assertSee('data-help-article-action="publish"', false)
            ->assertSee('Edit article', false)
            ->assertSee('Publish article', false);
    }

    public function test_help_image_upload_generates_alt_text_and_uses_preview_endpoint(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $category = HelpCategory::factory()->create(['audiences' => ['all']]);

        $this->actingAs($admin)->post(route('admin.help.articles.store'), [
            'help_category_id' => $category->id,
            'title' => 'Screenshot guide',
            'summary' => 'A guide with a screenshot.',
            'audiences' => ['all'],
            'status' => 'draft',
            'sort_order' => 0,
            'sections' => [['heading' => 'Open settings', 'body' => 'Choose settings.', 'image' => UploadedFile::fake()->image('settings.png')]],
        ])->assertRedirect();

        $article = HelpArticle::query()->where('title', 'Screenshot guide')->firstOrFail();
        $section = $article->sections()->firstOrFail();
        $this->assertSame('Screenshot for Open settings', $section->image_alt_text);
        $this->actingAs($admin)->get(route('admin.help.articles.preview', $article))
            ->assertOk()->assertSee(route('admin.help.articles.section.image', [$article, $section]), false);
    }

    private function dom(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }
}
