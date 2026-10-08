<?php

namespace Database\Factories;

use App\Models\PlatformFeedback;
use App\Models\PlatformFeedbackMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlatformFeedbackMessage> */
class PlatformFeedbackMessageFactory extends Factory
{
    protected $model = PlatformFeedbackMessage::class;

    public function definition(): array
    {
        return [
            'platform_feedback_id' => PlatformFeedback::factory(),
            'sender_id' => User::factory(),
            'sender_role' => 'learner',
            'body' => fake()->paragraph(),
        ];
    }
}
