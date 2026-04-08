<?php

namespace Tests\Feature\Notifications;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDeepLinkRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_known_notification_with_valid_action_url_redirects_to_target(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $notification = $learner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'learner.update',
            'data' => [
                'title' => 'Open chat',
                'message' => 'Continue your conversation.',
                'action_url' => route('chat.page'),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect(route('chat.page'));
    }

    public function test_missing_action_url_falls_back_to_role_notification_index(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $notification = $instructor->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'instructor.update',
            'data' => [
                'title' => 'No link',
                'message' => 'Fallback should be used.',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.notifications.read', $notification->id))
            ->assertRedirect(route('instructor.notifications.index'));
    }

    public function test_external_action_url_falls_back_to_safe_internal_route(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $notification = $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'admin.update',
            'data' => [
                'title' => 'Unsafe target',
                'message' => 'Should not redirect externally.',
                'action_url' => 'https://example.com/external-target',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.notifications.read', $notification->id))
            ->assertRedirect(route('admin.notifications.index'));
    }
}
