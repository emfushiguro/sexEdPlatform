<?php

namespace Tests\Feature\Support;

use App\Models\PlatformFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformFeedbackValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_rejects_invalid_feedback_payload(): void
    {
        $user = User::factory()->create(['role' => 'learner', 'birthdate' => now()->subYears(25)]);
        $this->actingAs($user)->post(route('feedback.store'), [
            'type' => 'nope', 'subject' => '', 'description' => '', 'rating' => 9,
        ])->assertSessionHasErrors(['type', 'subject', 'description', 'rating']);
    }

    public function test_description_accepts_500_characters_and_rejects_501(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'type' => 'general',
            'subject' => 'Exactly at the limit',
            'description' => str_repeat('a', 500),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('platform_feedback', [
            'user_id' => $user->id,
            'subject' => 'Exactly at the limit',
        ]);

        $this->post(route('feedback.store'), [
            'type' => 'general',
            'subject' => 'Over the limit',
            'description' => str_repeat('b', 501),
        ])->assertSessionHasErrors('description');

        $this->assertFalse(PlatformFeedback::query()->where('subject', 'Over the limit')->exists());
    }

    public function test_rating_is_optional_and_must_be_between_one_and_five(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $this->actingAs($user)->post(route('feedback.store'), [
            'type' => 'general',
            'subject' => 'No rating',
            'description' => 'The optional rating may be omitted.',
        ])->assertSessionHasNoErrors();

        $this->post(route('feedback.store'), [
            'type' => 'general',
            'subject' => 'Bad rating',
            'description' => 'The rating is outside the supported range.',
            'rating' => 6,
        ])->assertSessionHasErrors('rating');
    }
}
