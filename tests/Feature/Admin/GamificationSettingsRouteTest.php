<?php

namespace Tests\Feature\Admin;

use App\Models\GamificationPolicy;
use App\Models\User;
use Database\Seeders\GamificationPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationSettingsRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationPolicySeeder::class);
    }

    public function test_admin_can_open_gamification_settings_page(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification-settings.index'))
            ->assertOk()
            ->assertSee('Gamification Settings', false);
    }

    public function test_admin_can_update_gamification_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->put(route('admin.gamification-settings.update'), [
                'policy_payload' => [
                    'points_config' => [
                        'topic_complete_points' => 23,
                    ],
                ],
                'change_summary' => 'Update topic points.',
                'version_label' => 'topic-23',
            ])
            ->assertRedirect(route('admin.gamification-settings.index'));

        $this->assertSame(23, (int) (GamificationPolicy::latestActive()?->policy_payload['points_config']['topic_complete_points'] ?? 0));
    }

    public function test_admin_can_restore_gamification_settings_version(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->put(route('admin.gamification-settings.update'), [
                'policy_payload' => [
                    'points_config' => [
                        'topic_complete_points' => 66,
                    ],
                ],
                'version_label' => 'topic-66',
            ])
            ->assertRedirect(route('admin.gamification-settings.index'));

        $baselineVersion = \App\Models\GamificationPolicyVersion::query()->orderBy('id')->first();

        $this->assertNotNull($baselineVersion);

        $this->actingAs($admin)
            ->post(route('admin.gamification-settings.restore', (int) $baselineVersion->id))
            ->assertRedirect(route('admin.gamification-settings.index'));

        $this->assertSame(10, (int) (GamificationPolicy::latestActive()?->policy_payload['points_config']['topic_complete_points'] ?? 0));
    }

    public function test_non_admin_is_denied_access(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->actingAs($learner)
            ->get(route('admin.gamification-settings.index'))
            ->assertForbidden();
    }
}
