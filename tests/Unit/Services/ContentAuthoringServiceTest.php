<?php

namespace Tests\Unit\Services;

use App\Services\Content\ContentAuthoringService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentAuthoringServiceTest extends TestCase
{
    public function test_instructor_payload_rejects_legacy_kids_targeting(): void
    {
        $service = app(ContentAuthoringService::class);

        $this->expectException(ValidationException::class);

        $service->toInstructorDraftPayload([
            'title' => 'Body Safety Basics',
            'description' => 'Module description',
            'age_bracket' => 'kids',
            'enrollment_mode' => 'manual',
            'access_type' => 'paid',
            'price_amount' => 149.00,
            'price_currency' => 'php',
        ], 42);
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

        $this->assertSame(10, $payload['min_age']);
        $this->assertSame(17, $payload['max_age']);
        $this->assertFalse($payload['is_premium']);
        $this->assertNull($payload['price_amount']);
        $this->assertSame('PHP', $payload['price_currency']);
    }
}
