<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\ModerationCaseSource;
use App\Enums\ModerationCaseStatus;
use App\Models\ModerationCase;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\DatabaseTestCase;

class ModerationCaseSchemaTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_moderation_cases_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('moderation_cases'));

        $this->assertTrue(Schema::hasColumns('moderation_cases', [
            'case_reference_code',
            'reporter_id',
            'reported_user_id',
            'content_type',
            'content_id',
            'case_source',
            'status',
            'severity_level',
            'decision',
            'reviewed_by_admin_id',
            'reviewed_at',
            'notes',
            'metadata',
        ]));
    }

    public function test_case_reference_code_is_unique(): void
    {
        $reportedUser = User::factory()->create();

        ModerationCase::query()->create([
            'case_reference_code' => 'CASE-MOD-2026-000001',
            'reported_user_id' => $reportedUser->id,
            'content_type' => 'module',
            'content_id' => 101,
            'case_source' => ModerationCaseSource::LearnerReport,
            'status' => ModerationCaseStatus::Reported,
            'metadata' => ['trace' => 'first-entry'],
        ]);

        $this->expectException(QueryException::class);

        ModerationCase::query()->create([
            'case_reference_code' => 'CASE-MOD-2026-000001',
            'reported_user_id' => $reportedUser->id,
            'content_type' => 'module',
            'content_id' => 102,
            'case_source' => ModerationCaseSource::LearnerReport,
            'status' => ModerationCaseStatus::Reported,
            'metadata' => ['trace' => 'duplicate-entry'],
        ]);
    }

    public function test_reporter_is_nullable(): void
    {
        $reportedUser = User::factory()->create();

        $case = ModerationCase::query()->create([
            'case_reference_code' => 'CASE-MOD-2026-000002',
            'reporter_id' => null,
            'reported_user_id' => $reportedUser->id,
            'content_type' => 'message',
            'content_id' => 77,
            'case_source' => ModerationCaseSource::ChatReport,
            'status' => ModerationCaseStatus::Reported,
            'metadata' => ['trace' => 'nullable-reporter'],
        ]);

        $this->assertDatabaseHas('moderation_cases', [
            'id' => $case->id,
            'reporter_id' => null,
        ]);
    }
}
