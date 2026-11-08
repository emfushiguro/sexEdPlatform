<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEndpointRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_step_is_limited_after_thirty_requests_per_ip(): void
    {
        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->post('/register', [
                'first_name' => 'Rate',
                'last_name' => 'Tester',
                'birthdate' => '2000-01-01',
            ])->assertRedirect('/register/account');
        }

        $this->post('/register', [
            'first_name' => 'Rate',
            'last_name' => 'Tester',
            'birthdate' => '2000-01-01',
        ])->assertStatus(429);
    }
}
