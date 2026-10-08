<?php

namespace Tests\Feature\Support;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Database\Seeders\HelpCenterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class HelpCenterSeederTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_help_center_seeder_is_idempotent_and_seeds_the_initial_categories_and_guides(): void
    {
        $this->seed(HelpCenterSeeder::class);
        $this->seed(HelpCenterSeeder::class);

        $this->assertSame(13, HelpCategory::query()->count());
        $this->assertSame(13, HelpArticle::query()->count());
        $this->assertDatabaseHas('help_articles', ['slug' => 'navigate-conscious-connections']);
        $this->assertDatabaseHas('help_articles', ['slug' => 'use-community-hub-safely']);
        $this->assertDatabaseHas('help_articles', ['slug' => 'report-unsafe-or-inappropriate-content']);
        $this->assertDatabaseMissing('users', ['email' => 'help-center@consciousconnections.local']);
        $article = HelpArticle::query()->where('slug', 'navigate-conscious-connections')->firstOrFail();
        $this->assertNull($article->created_by);
        $this->assertNull($article->updated_by);
    }
}
