<?php

namespace Tests\Feature\Auth;

use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_instructor_login_redirect_does_not_flash_shared_login_message(): void
    {
        $response = $this->get(route('instructor.login'));

        $response->assertRedirect(route('login'));
        $this->assertFalse($response->getSession()->has('info'));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('learner.dashboard', absolute: false));
    }

    public function test_users_can_authenticate_with_a_non_gmail_email_address(): void
    {
        $user = User::factory()->create(['email' => 'learner@yahoo.com']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('learner.dashboard', absolute: false));
    }

    public function test_users_can_authenticate_with_an_rfc_valid_email_that_filter_validate_email_may_reject(): void
    {
        $user = User::factory()->create(['email' => '"quoted"@example.com']);

        $response = $this->post('/login', [
            'email' => '"quoted"@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('learner.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_legacy_guardian_created_dependent_is_verified_after_login(): void
    {
        $child = User::factory()->unverified()->create(['role' => 'learner']);
        $guardian = User::factory()->create();
        ParentChildAccount::create(['parent_user_id' => $guardian->id, 'child_user_id' => $child->id,
            'verification_document_path' => 'child-verifications/test.pdf', 'verification_status' => 'pending']);

        $this->post('/login', ['email' => $child->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($child);
        $this->assertNotNull($child->fresh()->email_verified_at);
        $this->assertSame('pending', $child->parentChildLink()->firstOrFail()->verification_status);
        $this->get(route('learner.dashboard'))->assertRedirect(route('child.verification.status'));
    }

    public function test_unverified_invited_or_unrelated_learner_is_not_automatically_verified(): void
    {
        $child = User::factory()->unverified()->create(['role' => 'learner']);
        $guardian = User::factory()->create();
        ParentChildAccount::create(['parent_user_id' => $guardian->id, 'child_user_id' => $child->id]);
        $this->post('/login', ['email' => $child->email, 'password' => 'password'])->assertRedirect();
        $this->assertNull($child->fresh()->email_verified_at);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
