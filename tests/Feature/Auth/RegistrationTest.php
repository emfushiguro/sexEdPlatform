<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_account_info_screen_redirects_to_register_without_session(): void
    {
        // Guard: visiting step 2 without completing step 1 redirects back to start
        $response = $this->get('/register/account');

        $response->assertRedirect('/register');
    }

    public function test_personal_info_step_stores_session_and_redirects_to_account_step(): void
    {
        $response = $this->post('/register', [
            'first_name'     => 'Juan',
            'last_name'      => 'dela Cruz',
            'birthdate'      => '2000-01-01',
        ]);

        $response->assertRedirect('/register/account');
        $this->assertNotNull(session('pending_personal_info'));
    }

    public function test_under_13_redirects_to_parent_required(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Kiddo',
            'last_name'  => 'Test',
            'birthdate'  => now()->subYears(10)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('parent.registration.required'));
        $this->assertTrue((bool) session('is_parent_registration'));
    }

    public function test_new_users_can_register_via_two_step_flow(): void
    {
        // Step 1: submit personal info
        $this->withSession(['pending_personal_info' => [
            'first_name'     => 'Juan',
            'middle_initial' => null,
            'last_name'      => 'dela Cruz',
            'suffix'         => null,
            'birthdate'      => '2000-01-01',
            'age'            => 25,
        ]]);

        // Step 2: submit account info
        $response = $this->post('/register/account', [
            'email'                 => 'juan@gmail.com',
            'password'              => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
    }
}
