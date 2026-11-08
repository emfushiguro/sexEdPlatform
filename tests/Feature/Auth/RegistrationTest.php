<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'first_name' => 'Juan',
            'last_name' => 'dela Cruz',
            'birthdate' => '2000-01-01',
        ]);

        $response->assertRedirect('/register/account');
        $this->assertNotNull(session('pending_personal_info'));
    }

    public function test_under_13_redirects_to_parent_required(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Kiddo',
            'last_name' => 'Test',
            'birthdate' => now()->subYears(10)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('parent.registration.required'));
        $this->assertTrue((bool) session('is_parent_registration'));
    }

    public function test_new_users_can_register_via_two_step_flow(): void
    {
        // Step 1: submit personal info
        $this->withSession(['pending_personal_info' => [
            'first_name' => 'Juan',
            'middle_initial' => null,
            'last_name' => 'dela Cruz',
            'suffix' => null,
            'birthdate' => '2000-01-01',
            'age' => 25,
        ]]);

        // Step 2: submit account info
        $response = $this->post('/register/account', [
            'email' => 'juan@gmail.com',
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'juan@gmail.com')->firstOrFail();
        $this->assertSame('adult', $user->identityVerifications()->sole()->pathway);
        $this->assertSame(['created'], $user->identityVerifications()->sole()->audits()->pluck('action')->all());
    }

    public function test_new_thirteen_year_old_gets_one_teen_case(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-24 10:00:00'));
        $this->withSession(['pending_personal_info' => [
            'first_name' => 'Teen', 'last_name' => 'Learner',
            'birthdate' => '2013-09-24', 'age' => 99,
        ]]);

        $this->post('/register/account', [
            'email' => 'teenlearner@gmail.com',
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'teenlearner@gmail.com')->firstOrFail();
        $this->assertSame('teen', $user->identityVerifications()->sole()->pathway);
    }

    #[DataProvider('providerNeutralEmails')]
    public function test_registration_accepts_provider_neutral_email_domains(string $email): void
    {
        $this->withSession(['pending_personal_info' => [
            'first_name' => 'Provider',
            'middle_initial' => null,
            'last_name' => 'Tester',
            'suffix' => null,
            'birthdate' => '2000-01-01',
            'age' => 25,
        ]]);

        $response = $this->post('/register/account', [
            'email' => $email,
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ]);

        $response->assertRedirect(route('verification.notice'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => strtolower(trim($email)),
        ]);
    }

    public static function providerNeutralEmails(): array
    {
        return [
            'Gmail' => ['provider.test@gmail.com'],
            'Yahoo' => ['provider.test@yahoo.com'],
            'Outlook' => ['provider.test@outlook.com'],
            'Education' => ['provider.test@school.edu.ph'],
            'Custom' => ['provider.test@custom.example'],
            'Privacy relay' => ['provider.test@privaterelay.appleid.com'],
            'Temporary' => ['provider.test@temporary-mail.com'],
            'Non-resolving' => ['provider.test@example.invalid'],
        ];
    }

    #[DataProvider('malformedEmails')]
    public function test_registration_rejects_clearly_malformed_email_formats(string $email): void
    {
        $this->withSession(['pending_personal_info' => [
            'first_name' => 'Invalid',
            'middle_initial' => null,
            'last_name' => 'Tester',
            'suffix' => null,
            'birthdate' => '2000-01-01',
            'age' => 25,
        ]]);

        $this->post('/register/account', [
            'email' => $email,
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public static function malformedEmails(): array
    {
        return [
            'plain address' => ['plain-address'],
            'missing domain' => ['person@'],
            'missing local part' => ['@example.com'],
            'doubled at sign' => ['person@@example.com'],
            'embedded whitespace' => ['person example@example.com'],
        ];
    }

    public function test_registration_normalizes_mixed_case_and_surrounding_whitespace(): void
    {
        $this->withSession(['pending_personal_info' => [
            'first_name' => 'Normalized',
            'middle_initial' => null,
            'last_name' => 'Tester',
            'suffix' => null,
            'birthdate' => '2000-01-01',
            'age' => 25,
        ]]);

        $this->post('/register/account', [
            'email' => '  Provider.Test@Yahoo.COM  ',
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'provider.test@yahoo.com']);
    }

    public function test_registration_still_rejects_duplicate_email_addresses(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $this->withSession(['pending_personal_info' => [
            'first_name' => 'Duplicate',
            'middle_initial' => null,
            'last_name' => 'Tester',
            'suffix' => null,
            'birthdate' => '2000-01-01',
            'age' => 25,
        ]]);

        $this->post('/register/account', [
            'email' => 'duplicate@example.com',
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ])
            ->assertSessionHasErrors([
                'email' => 'This email address is already registered.',
            ]);

        $this->assertGuest();
    }
}
