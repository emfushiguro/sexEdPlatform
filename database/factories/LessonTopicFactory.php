<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonTopicFactory extends Factory
{
    protected $model = LessonTopic::class;

    public function definition(): array
    {
        return [
            'lesson_id'        => Lesson::factory(),
            'title'            => fake()->sentence(4),
            'type'             => 'text',
            'text_content'     => '<p>' . fake()->paragraph() . '</p>',
            'duration'         => 5,
            'is_prerequisite'  => false,
            'order'            => 1,
        ];
    }
}
