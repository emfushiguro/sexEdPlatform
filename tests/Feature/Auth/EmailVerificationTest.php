<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_gmail_user_sees_gmail_inbox_shortcut(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'notice@gmail.com']);

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertOk()
            ->assertSee('Open Gmail', false)
            ->assertSee('https://mail.google.com', false);
    }

    public function test_non_gmail_user_does_not_see_gmail_inbox_shortcut(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'notice@yahoo.com']);

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertOk()
            ->assertDontSee('Open Gmail', false)
            ->assertDontSee('https://mail.google.com', false);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('verification.notice', absolute: false));
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
