<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearnerNotificationReadFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_opening_dropdown_endpoint_marks_unread_notifications_as_read(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->createNotification($learner, [
            'title' => 'Unread 1',
            'message' => 'First unread notification',
        ]);

        $this->createNotification($learner, [
            'title' => 'Unread 2',
            'message' => 'Second unread notification',
        ]);

        $response = $this->actingAs($learner)->postJson(route('learner.notifications.dropdown-open'));

        $response->assertOk()->assertJson([
            'ok' => true,
            'updated' => 2,
        ]);

        $this->assertSame(0, $learner->fresh()->unreadNotifications()->count());
    }

    public function test_clicking_notification_deep_links_when_action_url_exists(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $notification = $this->createNotification($learner, [
            'title' => 'Go to chat',
            'message' => 'Open your conversation.',
            'action_url' => route('chat.page'),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect(route('chat.page'));
    }

    public function test_clicking_notification_without_action_url_falls_back_to_notifications_page(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $notification = $this->createNotification($learner, [
            'title' => 'Informational update',
            'message' => 'No deep link available.',
        ]);

        $this->actingAs($learner)
            ->get(route('learner.notifications.read', $notification->id))
            ->assertRedirect(route('learner.notifications.index'));
    }

    private function createNotification(User $learner, array $payload)
    {
        return $learner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'learner.update',
            'data' => $payload,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
