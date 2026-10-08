<?php

namespace Database\Factories;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HelpArticle> */
class HelpArticleFactory extends Factory
{
    protected $model = HelpArticle::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'help_category_id' => HelpCategory::factory(),
            'created_by' => User::factory()->state(['role' => 'admin']),
            'updated_by' => null,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'summary' => fake()->sentence(),
            'keywords' => ['help', 'guide'],
            'audiences' => ['all'],
            'status' => HelpArticleStatus::Draft,
            'sort_order' => 0,
            'published_at' => null,
        ];
    }
}
