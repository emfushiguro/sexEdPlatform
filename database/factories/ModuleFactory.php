<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'thumbnail' => null,
            'min_age' => 5,
            'max_age' => 18,
            'age_specific_content' => null,
            'order' => fake()->numberBetween(1, 20),
            'duration_minutes' => fake()->numberBetween(15, 120),
            'is_published' => true,
            'is_premium' => false,
            'enrollment_mode' => 'auto',
            'final_quiz_id' => null,
            'certificate_pass_score' => 70,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_premium' => true,
        ]);
    }
}
