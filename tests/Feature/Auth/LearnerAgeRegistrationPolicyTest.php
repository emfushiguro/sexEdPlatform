<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerAgeRegistrationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_branches_only_on_the_live_date_of_birth_policy(): void
    {
        foreach ([9, 101] as $age) {
            $this->post('/register', $this->personalInfo($age))
                ->assertSessionHasErrors('birthdate');
        }

        foreach ([10, 17] as $age) {
            $this->post('/register', $this->personalInfo($age))
                ->assertRedirect(route('minor.registration.create'))
                ->assertSessionHas('pending_minor_registration');
        }

        foreach ([18, 100] as $age) {
            $this->post('/register', $this->personalInfo($age))
                ->assertRedirect(route('register.account'))
                ->assertSessionHas('pending_personal_info');
        }
    }

    public function test_crafted_age_and_category_values_cannot_create_a_teenager_independently(): void
    {
        $this->post('/register', array_merge($this->personalInfo(15), [
            'age' => 99,
            'kids' => '0',
            'category' => 'adults',
            'age_bracket_cached' => 'adults',
            'account_type' => User::ACCOUNT_TYPE_LEARNER_ADULT,
        ]))->assertRedirect(route('minor.registration.create'));

        $this->withSession([
            'pending_personal_info' => [
                'first_name' => 'Crafted',
                'last_name' => 'Teenager',
                'birthdate' => now()->subYears(15)->toDateString(),
                'age' => 99,
            ],
        ])->post('/register/account', [
            'email' => 'crafted-teen@example.test',
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ])->assertSessionHasErrors('birthdate');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'crafted-teen@example.test']);
    }

    public function test_adult_registration_still_requires_a_unique_genuine_email(): void
    {
        $this->post('/register', $this->personalInfo(18))
            ->assertRedirect(route('register.account'));

        $this->post('/register/account', [
            'email' => 'not-an-email',
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** @return array<string, string> */
    private function personalInfo(int $age): array
    {
        return [
            'first_name' => 'Policy',
            'last_name' => 'Boundary',
            'birthdate' => now()->subYears($age)->toDateString(),
        ];
    }
}
