<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Basic', 'Premium', 'Pro']);

        return [
            'name' => $name,
            'slug' => strtolower($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 99, 999),
            'features' => ['full_course_access' => true, 'certificates' => true],
            'trial_days' => 0,
            'max_users' => null,
            'max_modules' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
