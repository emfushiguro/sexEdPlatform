<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'module_id'    => Module::factory(),
            'title'        => fake()->sentence(4),
            'description'  => fake()->paragraph(),
            'order'        => 1,
            'duration'     => 30,
            'is_published' => true,
        ];
    }
}
