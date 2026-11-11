<?php

namespace Tests\Feature\Auth;

use App\Models\AdminMfaChallenge;
use App\Models\User;
use App\Notifications\AdminLoginCodeNotification;
use App\Services\Auth\AdminMfaService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminMfaAuthenticationTest extends TestCase
{
    public function test_admin_portal_validates_password_without_authenticating_and_redirects_to_mfa(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.mfa.show'));

        $this->assertGuest();
        $this->assertDatabaseHas('admin_mfa_challenges', ['user_id' => $admin->id]);
        Notification::assertSentTo($admin, AdminLoginCodeNotification::class);
    }

    public function test_general_login_intercepts_an_email_based_admin_before_normal_authentication(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.mfa.show'));

        $this->assertGuest();
        Notification::assertSentTo($admin, AdminLoginCodeNotification::class);
    }

    public function test_non_admin_login_retains_the_existing_authentication_flow(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $learner->assignRole('learner');

        $this->post('/login', [
            'email' => $learner->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($learner);
        $this->assertDatabaseCount('admin_mfa_challenges', 0);
    }

    public function test_verification_page_masks_email_and_success_authenticates_after_consumption(): void
    {
        [$admin, $code, $challenge] = $this->start();

        $this->get(route('admin.mfa.show'))
            ->assertOk()
            ->assertSee('Two-Step Verification', false)
            ->assertSee('a***@e***.com', false)
            ->assertDontSee($admin->email, false)
            ->assertSee('name="digits[]"', false)
            ->assertSee('data-otp-row', false)
            ->assertSee('flex-nowrap', false)
            ->assertSee(route('admin.mfa.resend'), false)
            ->assertSee(route('admin.mfa.cancel'), false);

        $this->post(route('admin.mfa.verify'), [
            'digits' => str_split($code),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($challenge->fresh()->consumed_at);
        $this->assertSame(
            $admin->id,
            session(AdminMfaService::VERIFIED_USER_KEY),
        );
    }

    public function test_invalid_code_returns_to_the_prompt_and_cancel_returns_to_login(): void
    {
        [$admin] = $this->start();

        $this->from(route('admin.mfa.show'))
            ->post(route('admin.mfa.verify'), ['digits' => str_split('999999')])
            ->assertRedirect(route('admin.mfa.show'))
            ->assertSessionHasErrors('code');

        $this->post(route('admin.mfa.cancel'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertNull(session(AdminMfaService::PENDING_CHALLENGE_KEY));
    }

    public function test_admin_logout_clears_session_and_records_audit_event(): void
    {
        [$admin, $code] = $this->start();
        $this->post(route('admin.mfa.verify'), ['digits' => str_split($code)]);

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'activity_type' => 'admin_mfa.logout',
        ]);
    }

    public function test_mfa_routes_without_a_pending_challenge_return_to_admin_login(): void
    {
        $this->get(route('admin.mfa.show'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.mfa.verify'), ['digits' => str_split('123456')])
            ->assertRedirect(route('admin.login'));
    }

    public function test_request_role_parameter_cannot_turn_a_learner_into_an_admin_challenge(): void
    {
        Notification::fake();
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $learner->assignRole('learner');

        $this->post(route('admin.login.submit'), [
            'email' => $learner->email,
            'password' => 'password',
            'role' => 'admin',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('admin_mfa_challenges', 0);
        Notification::assertNothingSent();
    }

    private function start(): array
    {
        Notification::fake();
        $admin = $this->admin(['email' => 'admin@example.com']);
        $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $notification = Notification::sent($admin, AdminLoginCodeNotification::class)->last();
        $challenge = AdminMfaChallenge::query()->where('user_id', $admin->id)->latest()->firstOrFail();

        return [$admin, $notification->code, $challenge];
    }

    private function admin(array $attributes = []): User
    {
        $admin = User::factory()->create(array_replace([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ], $attributes));
        $admin->assignRole('admin');

        return $admin;
    }
}
