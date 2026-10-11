<?php

namespace Tests\Unit\Support;

use App\Enums\HelpArticleStatus;
use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use App\Enums\TestimonialStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\PlatformFeedback;
use App\Models\PlatformFeedbackMessage;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class SupportModelTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_help_article_casts_status_audiences_and_relations(): void
    {
        $category = HelpCategory::factory()->create();
        $article = HelpArticle::factory()->for($category, 'category')->create([
            'status' => HelpArticleStatus::Published,
            'audiences' => ['all'],
            'published_at' => now(),
        ]);

        $this->assertSame(HelpArticleStatus::Published, $article->status);
        $this->assertSame(['all'], $article->audiences);
        $this->assertSame($category->id, $article->category->id);
    }

    public function test_published_article_scope_filters_status_category_and_audience(): void
    {
        $category = HelpCategory::factory()->create(['is_active' => true]);
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
        HelpArticle::factory()->for($category, 'category')->create([
            'audiences' => ['learner'],
            'status' => HelpArticleStatus::Draft,
            'published_at' => null,
        ]);

        $this->assertEqualsCanonicalizing(
            [$public->id],
            HelpArticle::query()->publishedForAudience('learner')->pluck('id')->all()
        );
        $this->assertNotContains($adminOnly->id, HelpArticle::query()->publishedForAudience('learner')->pluck('id')->all());
    }

    public function test_feedback_and_testimonial_casts_and_public_scope_are_consistent(): void
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears(25),
        ]);
        $feedback = PlatformFeedback::factory()->for($user)->create([
            'type' => PlatformFeedbackType::General,
            'status' => PlatformFeedbackStatus::Reviewed,
            'testimonial_consent' => true,
        ]);
        $testimonial = Testimonial::factory()->for($user)->create([
            'status' => TestimonialStatus::Published,
            'published_at' => now(),
            'consent_given' => true,
        ]);

        $this->assertSame(PlatformFeedbackType::General, $feedback->type);
        $this->assertSame(PlatformFeedbackStatus::Reviewed, $feedback->status);
        $this->assertNull($testimonial->platform_feedback_id);
        $this->assertSame([$testimonial->id], Testimonial::query()->publiclyVisible()->pluck('id')->all());
    }

    public function test_feedback_message_factory_builds_ticket_and_sender_relations(): void
    {
        $message = PlatformFeedbackMessage::factory()->create();

        $this->assertInstanceOf(PlatformFeedback::class, $message->ticket);
        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertSame($message->platform_feedback_id, $message->ticket->id);
        $this->assertSame($message->sender_id, $message->sender->id);
    }
}
