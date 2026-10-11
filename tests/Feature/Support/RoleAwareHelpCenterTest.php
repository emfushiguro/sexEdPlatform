<?php

namespace Tests\Feature\Support;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class RoleAwareHelpCenterTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_authenticated_help_uses_the_learner_shell_for_learner_users(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $article = HelpArticle::factory()->for(HelpCategory::factory(), 'category')->create([
            'audiences' => ['learner'],
            'status' => HelpArticleStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('help.show', $article->slug))
            ->assertOk()
            ->assertSee('Dashboard');
    }
}
