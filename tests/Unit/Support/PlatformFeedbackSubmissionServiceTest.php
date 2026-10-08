<?php

namespace Tests\Unit\Support;

use App\Models\PlatformFeedback;
use App\Models\User;
use App\Services\Support\PlatformFeedbackSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformFeedbackSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_is_private_and_minor_consent_is_stripped(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'learner', 'account_type' => User::ACCOUNT_TYPE_LEARNER_TEEN, 'birthdate' => now()->subYears(16)]);
        $feedback = app(PlatformFeedbackSubmissionService::class)->submit($user, [
            'type' => 'general', 'subject' => 'A note', 'description' => 'Details', 'affected_path' => 'https://evil.test/private?q=1',
            'testimonial_consent' => true, 'testimonial_display_name' => 'Minor', 'may_contact' => true,
        ], null, str_repeat('Browser', 200));

        $this->assertStringStartsWith('FB-'.now()->format('Ymd').'-', $feedback->reference_number);
        $this->assertNull($feedback->affected_path);
        $this->assertDatabaseCount('testimonials', 0);
        $this->assertSame(500, strlen($feedback->user_agent));
        $this->assertDatabaseHas('platform_feedback', ['id' => $feedback->id, 'user_role' => 'learner']);
    }
}
