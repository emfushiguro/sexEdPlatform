<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'title'         => fake()->sentence(3),
            'description'   => fake()->sentence(),
            'passing_score' => 70,
            'time_limit'    => null,
            'is_active'     => true,
        ];
    }
}
