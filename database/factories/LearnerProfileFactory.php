<?php

namespace Database\Factories;

use App\Models\LearnerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearnerProfile>
 */
class LearnerProfileFactory extends Factory
{
    protected $model = LearnerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => fake()->unique()->userName(),
            'username_changed_at' => null,
            'birthdate' => fake()->dateTimeBetween('-25 years', '-5 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'city_code' => null,
            'barangay' => null,
            'barangay_code' => null,
            'school' => fake()->company() . ' School',
            'bio' => fake()->sentence(),
            'avatar_path' => null,
            'about' => fake()->paragraph(),
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ];
    }

    public function kid(): static
    {
        return $this->state(fn (array $attributes) => [
            'birthdate' => fake()->dateTimeBetween('-12 years', '-5 years'),
            'requires_parental_consent' => true,
        ]);
    }

    public function teen(): static
    {
        return $this->state(fn (array $attributes) => [
            'birthdate' => fake()->dateTimeBetween('-17 years', '-13 years'),
            'requires_parental_consent' => true,
        ]);
    }

    public function adult(): static
    {
        return $this->state(fn (array $attributes) => [
            'birthdate' => fake()->dateTimeBetween('-40 years', '-18 years'),
            'requires_parental_consent' => false,
        ]);
    }
}
