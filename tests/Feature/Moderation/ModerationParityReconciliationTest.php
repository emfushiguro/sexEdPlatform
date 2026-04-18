<?php

namespace Tests\Feature\Moderation;

use App\Enums\ContentReportStatus;
use App\Enums\ContentReportTargetType;
use App\Models\ContentReport;
use App\Models\User;
use App\Services\Moderation\Backfill\CentralizedModerationBackfillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class ModerationParityReconciliationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_parity_reconciliation_reports_mismatches_clearly(): void
    {
        $reporter = $this->createUserWithRole('learner');
        $instructor = $this->createUserWithRole('instructor');

        ContentReport::query()->create([
            'reporter_id' => $reporter->id,
            'target_type' => ContentReportTargetType::Instructor,
            'target_id' => $instructor->id,
            'reason_code' => 'harmful_material',
            'status' => ContentReportStatus::Submitted,
        ]);

        $report = app(CentralizedModerationBackfillService::class)->reconcileParity();

        $mismatch = collect($report['mismatches'])->firstWhere('source', 'learner_report');

        $this->assertNotNull($mismatch);
        $this->assertSame(1, $mismatch['legacy_count']);
        $this->assertSame(0, $mismatch['centralized_count']);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
