<?php

namespace Tests\Feature\Admin;

use App\Models\GamificationPolicy;
use App\Models\GamificationPolicyVersion;
use App\Services\Gamification\GamificationPolicyAdminService;
use Database\Seeders\GamificationPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GamificationPolicyAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private GamificationPolicyAdminService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationPolicySeeder::class);
        $this->service = app(GamificationPolicyAdminService::class);
    }

    public function test_valid_update_creates_new_active_policy_and_version_snapshot(): void
    {
        $beforeVersionCount = GamificationPolicyVersion::query()->count();

        $updated = $this->service->updatePolicy([
            'points_config' => [
                'topic_complete_points' => 42,
            ],
        ], null, 'Adjusted topic points.', 'topic-42');

        $this->assertTrue($updated->is_active);
        $this->assertSame(42, $updated->policy_payload['points_config']['topic_complete_points']);
        $this->assertSame(1, GamificationPolicy::query()->active()->count());
        $this->assertGreaterThan($beforeVersionCount, GamificationPolicyVersion::query()->count());
    }

    public function test_invalid_payload_is_rejected_and_active_policy_remains_unchanged(): void
    {
        $initial = GamificationPolicy::latestActive();
        $initialPoints = (int) ($initial?->policy_payload['points_config']['topic_complete_points'] ?? 0);

        try {
            $this->service->updatePolicy([
                'points_config' => [
                    'topic_complete_points' => -20,
                ],
            ], null, 'Invalid payload test.');

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            $this->assertSame(1, GamificationPolicy::query()->active()->count());
            $this->assertSame(
                $initialPoints,
                (int) (GamificationPolicy::latestActive()?->policy_payload['points_config']['topic_complete_points'] ?? 0)
            );
        }
    }

    public function test_restore_creates_new_active_policy_from_historical_version(): void
    {
        $this->service->updatePolicy([
            'points_config' => [
                'topic_complete_points' => 99,
            ],
        ], null, 'Move topic points to 99.', 'topic-99');

        $baselineVersion = GamificationPolicyVersion::query()
            ->orderBy('id')
            ->first();

        $this->assertNotNull($baselineVersion);

        $restored = $this->service->restoreVersion((int) $baselineVersion->id, null, 'Restore baseline');

        $this->assertTrue($restored->is_active);
        $this->assertSame(10, $restored->policy_payload['points_config']['topic_complete_points']);
        $this->assertSame(1, GamificationPolicy::query()->active()->count());
    }
}
