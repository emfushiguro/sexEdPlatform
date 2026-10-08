<?php

namespace Database\Factories;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Testimonial> */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'platform_feedback_id' => null,
            'user_id' => User::factory(),
            'approved_by' => User::factory()->state(['role' => 'admin']),
            'display_name' => fake()->name(),
            'display_role' => 'Learner',
            'quotation' => fake()->sentence(12),
            'show_profile_image' => false,
            'show_role' => true,
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'consent_given' => true,
            'consented_at' => now(),
            'consent_withdrawn_at' => null,
            'status' => TestimonialStatus::Draft,
            'sort_order' => 0,
            'published_at' => null,
            'withdrawn_at' => null,
        ];
    }
}
