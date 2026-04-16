<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\GamificationPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationSettingsViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GamificationPolicySeeder::class);
    }

    public function test_tabs_render_on_gamification_settings_page(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification-settings.index'))
            ->assertOk()
            ->assertSee('Points', false)
            ->assertSee('Streak', false)
            ->assertSee('Leveling', false)
            ->assertSee('Shields', false)
            ->assertSee('Safeguards', false)
            ->assertSee('History', false);
    }

    public function test_current_active_values_are_prefilled(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification-settings.index'))
            ->assertOk()
            ->assertSee('name="points_config[topic_complete_points]"', false)
            ->assertSee('value="10"', false);
    }

    public function test_history_entries_render_after_update(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->put(route('admin.gamification-settings.update'), [
                'policy_payload' => [
                    'points_config' => [
                        'topic_complete_points' => 55,
                    ],
                ],
                'version_label' => 'history-entry-v55',
            ])
            ->assertRedirect(route('admin.gamification-settings.index'));

        $this->actingAs($admin)
            ->get(route('admin.gamification-settings.index'))
            ->assertOk()
            ->assertSee('history-entry-v55', false);
    }
}
