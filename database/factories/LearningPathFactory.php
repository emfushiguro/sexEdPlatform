<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LearningPath;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LearningPath> */
class LearningPathFactory extends Factory
{
    protected $model = LearningPath::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'thumbnail' => null,
            'status' => LearningPath::STATUS_DRAFT,
            'created_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LearningPath::STATUS_PUBLISHED,
        ]);
    }

    public function forCategories(array $categories): static
    {
        return $this->afterCreating(function (LearningPath $path) use ($categories): void {
            $normalizedCategories = collect($categories)
                ->map(fn ($category): string => trim((string) $category))
                ->filter()
                ->unique()
                ->values();

            foreach ($normalizedCategories as $category) {
                $path->learnerCategories()->create(['category' => $category]);
            }
        });
    }
}
