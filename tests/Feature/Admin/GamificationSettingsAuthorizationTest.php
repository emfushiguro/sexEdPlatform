<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\GamificationPolicy;
use App\Models\GamificationPolicyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GamificationSettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_admin_with_manage_system_settings_permission_can_update_and_restore_policy(): void
    {
        $this->configureAdminPermission(grant: true);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.gamification-settings.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.gamification-settings.update'), [
                'points_config' => [
                    'topic_complete_points' => 23,
                ],
                'change_summary' => 'Authorization test update.',
            ])
            ->assertRedirect(route('admin.gamification-settings.index'));

        $this->assertSame(
            23,
            (int) data_get(GamificationPolicy::latestActive()?->policy_payload, 'points_config.topic_complete_points', 0)
        );

        $earliestVersion = GamificationPolicyVersion::query()->oldest('id')->first();
        $this->assertNotNull($earliestVersion);

        $this->actingAs($admin)
            ->post(route('admin.gamification-settings.restore', $earliestVersion->id))
            ->assertRedirect(route('admin.gamification-settings.index'));
    }

    public function test_instructor_role_is_forbidden_from_gamification_settings_routes(): void
    {
        Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);

        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('admin.gamification-settings.index'))
            ->assertForbidden();
    }

    public function test_non_admin_role_is_forbidden_from_gamification_settings_routes(): void
    {
        Role::firstOrCreate(['name' => 'learner', 'guard_name' => 'web']);

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->actingAs($learner)
            ->get(route('admin.gamification-settings.index'))
            ->assertForbidden();
    }

    private function configureAdminPermission(bool $grant): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('manage system settings', 'web');
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        if ($grant) {
            if (!$adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }

            return;
        }

        if ($adminRole->hasPermissionTo($permission)) {
            $adminRole->revokePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
