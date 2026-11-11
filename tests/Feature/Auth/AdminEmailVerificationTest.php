<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminEmailVerificationTest extends TestCase
{
    public function test_admin_notification_uses_signed_guest_safe_url_that_verifies_without_login(): void
    {
        Event::fake();
        $admin = $this->admin();
        $url = (new CustomVerifyEmail)->toMail($admin)->viewData['verificationUrl'];

        $this->assertStringContainsString('secure-panel-access/verify-email', $url);
        $this->assertTrue(URL::hasValidSignature(Request::create($url)));

        $this->get($url)
            ->assertRedirect(route('admin.login'));

        Event::assertDispatched(Verified::class);
        $this->assertTrue($admin->fresh()->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function test_signed_admin_url_rejects_a_wrong_current_email_hash(): void
    {
        $admin = $this->admin();
        $url = URL::temporarySignedRoute(
            'admin.email-verification.verify',
            now()->addHour(),
            ['user' => $admin->id, 'hash' => sha1('wrong@example.com')],
        );

        $this->get($url)->assertForbidden();
        $this->assertFalse($admin->fresh()->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function test_tampered_admin_url_is_rejected_by_signature_middleware(): void
    {
        $admin = $this->admin();
        $url = (new CustomVerifyEmail)->toMail($admin)->viewData['verificationUrl'];

        $this->get($url.'&tampered=1')->assertForbidden();
        $this->assertFalse($admin->fresh()->hasVerifiedEmail());
    }

    public function test_non_admin_notification_keeps_the_existing_verification_route(): void
    {
        $learner = User::factory()->unverified()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $learner->assignRole('learner');

        $url = (new CustomVerifyEmail)->toMail($learner)->viewData['verificationUrl'];

        $this->assertStringNotContainsString('secure-panel-access/verify-email', $url);
        $this->assertStringContainsString('/verify-email/', $url);
    }

    private function admin(): User
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }
}
