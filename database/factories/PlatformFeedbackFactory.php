<?php

namespace Database\Factories;

use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use App\Models\PlatformFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PlatformFeedback> */
class PlatformFeedbackFactory extends Factory
{
    protected $model = PlatformFeedback::class;

    public function definition(): array
    {
        return [
            'reference_number' => 'FB-'.now()->format('Ymd').'-'.strtoupper(Str::random(8)),
            'submission_token' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'user_role' => 'learner',
            'type' => PlatformFeedbackType::General,
            'subject' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'rating' => null,
            'user_agent' => 'Testing/1.0',
            'may_contact' => false,
            'attachment_path' => null,
            'status' => PlatformFeedbackStatus::New,
            'internal_note' => null,
            'staff_response' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'resolved_at' => null,
        ];
    }
}
