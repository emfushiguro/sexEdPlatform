<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\ModerationCaseSource;
use App\Enums\ModerationCaseStatus;
use App\Enums\ViolationSeverity;
use App\Models\ModerationCase;
use App\Models\User;
use App\Services\Moderation\ViolationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\DatabaseTestCase;

class ViolationIssuanceRulesTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_violation_cannot_be_created_when_case_decision_is_not_confirmed_violation(): void
    {
        $service = app(ViolationService::class);

        $case = $this->createCaseWithDecision('no_violation');

        $this->expectException(\InvalidArgumentException::class);

        $service->issueFromCase($case, 'misconduct', ViolationSeverity::Moderate);
    }

    public function test_violation_can_be_created_from_confirmed_case(): void
    {
        $service = app(ViolationService::class);
        $case = $this->createCaseWithDecision('confirmed_violation');

        $violation = $service->issueFromCase($case, 'harassment', ViolationSeverity::Major);

        $this->assertDatabaseHas('violations', [
            'id' => $violation->id,
            'moderation_case_id' => $case->id,
            'user_id' => $case->reported_user_id,
            'violation_type' => 'harassment',
            'severity_level' => ViolationSeverity::Major->value,
        ]);
    }

    public function test_severity_based_expiry_is_applied(): void
    {
        Carbon::setTestNow('2026-04-18 09:00:00');

        $service = app(ViolationService::class);
        $case = $this->createCaseWithDecision('confirmed_violation');

        $minorViolation = $service->issueFromCase($case, 'spam', ViolationSeverity::Minor);
        $criticalViolation = $service->issueFromCase($case, 'threat', ViolationSeverity::Critical);

        $this->assertTrue($minorViolation->expires_at->equalTo(now()->addDays(30)));
        $this->assertTrue($criticalViolation->expires_at->equalTo(now()->addDays(365)));
    }

    private function createCaseWithDecision(string $decision): ModerationCase
    {
        return ModerationCase::query()->create([
            'case_reference_code' => 'CASE-MOD-2026-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'reported_user_id' => User::factory()->create()->id,
            'content_type' => 'message',
            'content_id' => random_int(1, 999999),
            'case_source' => ModerationCaseSource::ChatReport,
            'status' => ModerationCaseStatus::Investigating,
            'decision' => $decision,
            'metadata' => ['seed' => 'violation-rules-test'],
        ]);
    }
}
