<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Schema;
use Tests\DatabaseTestCase;

class AdminInstructorModerationSchemaTest extends DatabaseTestCase
{
    public function test_instructor_moderation_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('instructor_moderation_profiles'));
        $this->assertTrue(Schema::hasTable('instructor_violation_histories'));

        $this->assertTrue(Schema::hasColumns('instructor_moderation_profiles', [
            'user_id',
            'warning_count',
            'current_restriction_status',
            'restriction_starts_at',
            'restriction_ends_at',
            'last_violation_at',
            'escalation_level',
        ]));

        $this->assertTrue(Schema::hasColumns('instructor_violation_histories', [
            'user_id',
            'module_id',
            'module_review_request_id',
            'reason_code',
            'guidance_note',
            'violation_sequence',
            'suggested_penalty_action',
            'confirmed_penalty_action',
            'confirmed_by_admin_id',
        ]));
    }
}
