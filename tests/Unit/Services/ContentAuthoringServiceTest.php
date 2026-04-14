<?php

namespace Tests\Unit\Services;

use App\Services\Content\ContentAuthoringService;
use Tests\TestCase;

class ContentAuthoringServiceTest extends TestCase
{
    public function test_instructor_payload_sets_draft_defaults_and_age_mapping(): void
    {
        $service = app(ContentAuthoringService::class);

        $payload = $service->toInstructorDraftPayload([
            'title' => 'Body Safety Basics',
            'description' => 'Module description',
            'age_bracket' => 'kids',
            'enrollment_mode' => 'manual',
            'access_type' => 'paid',
            'price_amount' => 149.00,
            'price_currency' => 'php',
        ], 42);

        $this->assertSame('Body Safety Basics', $payload['title']);
        $this->assertSame(5, $payload['min_age']);
        $this->assertSame(12, $payload['max_age']);
        $this->assertSame('draft', $payload['current_review_status']);
        $this->assertSame('instructor', $payload['content_owner_type']);
        $this->assertFalse($payload['is_published']);
        $this->assertTrue($payload['is_premium']);
        $this->assertSame('PHP', $payload['price_currency']);
        $this->assertSame(42, $payload['created_by']);
    }

    public function test_admin_payload_normalizes_to_free_profile(): void
    {
        $service = app(ContentAuthoringService::class);

        $payload = $service->toAdminPayload([
            'title' => 'Official Platform Guide',
            'description' => 'Official module',
            'age_bracket' => 'teens',
            'enrollment_mode' => 'auto',
        ]);

        $this->assertSame(13, $payload['min_age']);
        $this->assertSame(17, $payload['max_age']);
        $this->assertFalse($payload['is_premium']);
        $this->assertNull($payload['price_amount']);
        $this->assertSame('PHP', $payload['price_currency']);
    }
}
