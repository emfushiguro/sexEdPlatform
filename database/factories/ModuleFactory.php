<?php

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'title'           => fake()->sentence(3),
            'description'     => fake()->paragraph(),
            'min_age'         => 5,
            'max_age'         => 12,
            'order'           => 1,
            'duration_minutes' => 30,
            'is_published'    => true,
            'is_premium'      => false,
            'enrollment_mode' => 'auto',
        ];
    }
}
