<?php

namespace Tests\Feature\Support;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\HelpArticleVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class HelpArticleHelpfulnessTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_one_helpfulness_vote(): void
    {
        $user = User::factory()->create(['role' => 'learner']);
        $article = HelpArticle::factory()->for(HelpCategory::factory(), 'category')->create([
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($user)->put(route('help.helpfulness.update', $article), ['is_helpful' => true])->assertRedirect();
        $this->actingAs($user)->put(route('help.helpfulness.update', $article), ['is_helpful' => false])->assertRedirect();

        $this->assertSame(1, HelpArticleVote::query()->count());
        $this->assertDatabaseHas('help_article_votes', [
            'help_article_id' => $article->id,
            'user_id' => $user->id,
            'is_helpful' => false,
        ]);
    }

    public function test_guest_cannot_vote_and_inaccessible_article_is_not_revealed(): void
    {
        $article = HelpArticle::factory()->for(HelpCategory::factory(), 'category')->create([
            'status' => HelpArticleStatus::Draft,
        ]);

        $this->put(route('help.helpfulness.update', $article), ['is_helpful' => true])->assertRedirect(route('login'));
        $user = User::factory()->create(['role' => 'learner']);
        $this->actingAs($user)->put(route('help.helpfulness.update', $article), ['is_helpful' => true])->assertNotFound();
    }
}
