<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\AdminMfaService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminMfaMiddlewareTest extends TestCase
{
    public function test_legacy_admin_session_without_mfa_is_logged_out_and_redirected(): void
    {
        $admin = $this->admin();

        $this->actingAsWithoutAdminMfa($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_json_admin_request_without_mfa_returns_unauthenticated_json(): void
    {
        Route::middleware(['web', 'auth', 'role:admin', 'admin.mfa'])
            ->get('/_test/admin-mfa-json', fn () => response()->json(['ok' => true]));

        $this->actingAsWithoutAdminMfa($this->admin())
            ->getJson('/_test/admin-mfa-json')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Admin multi-factor authentication is required.',
            ]);

        $this->assertGuest();
    }

    public function test_stale_credential_fingerprint_is_rejected(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $admin->forceFill(['password' => Hash::make('changed-password')])->save();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_admin_who_becomes_inactive_is_rejected_even_with_a_valid_marker(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $admin->forceFill(['status' => User::STATUS_INACTIVE])->save();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_normal_acting_as_admin_helper_carries_valid_mfa_state(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_marker_for_another_user_is_rejected(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->actingAsWithoutAdminMfa($admin)
            ->withSession(app(AdminMfaService::class)->verifiedSessionData($other))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    private function admin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }
}
