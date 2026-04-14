<?php

namespace Tests\Feature\Rbac;

use App\Services\Content\ContentAuthoringService;
use Tests\TestCase;

class RbacSharedContentDomainConsistencyTest extends TestCase
{
    public function test_admin_and_instructor_payload_builders_share_age_bracket_mapping(): void
    {
        $service = app(ContentAuthoringService::class);

        $adminPayload = $service->toAdminPayload([
            'title' => 'Admin Module',
            'description' => 'A',
            'age_bracket' => 'adults',
            'enrollment_mode' => 'auto',
        ]);

        $instructorPayload = $service->toInstructorDraftPayload([
            'title' => 'Instructor Module',
            'description' => 'B',
            'age_bracket' => 'adults',
            'enrollment_mode' => 'manual',
            'access_type' => 'free',
        ], 7);

        $this->assertSame(18, $adminPayload['min_age']);
        $this->assertSame(100, $adminPayload['max_age']);
        $this->assertSame(18, $instructorPayload['min_age']);
        $this->assertSame(100, $instructorPayload['max_age']);
    }
}
