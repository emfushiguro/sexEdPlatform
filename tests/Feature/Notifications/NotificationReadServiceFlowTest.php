<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Services\Notification\NotificationReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationReadServiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_all_read_resets_unread_to_zero(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationReadService::class);

        $this->seedUnreadNotifications($user, 3);

        $updatedCount = $service->markAllRead($user);

        $this->assertSame(3, $updatedCount);
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_dropdown_open_mark_read_is_idempotent(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationReadService::class);

        $this->seedUnreadNotifications($user, 2);

        $firstPass = $service->markAllReadOnDropdownOpen($user);
        $secondPass = $service->markAllReadOnDropdownOpen($user->fresh());

        $this->assertSame(2, $firstPass);
        $this->assertSame(0, $secondPass);
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_mark_one_read_sets_read_at_on_target_notification(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationReadService::class);

        $notificationId = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $notificationId,
            'type' => 'test.notification',
            'data' => [
                'title' => 'Read me',
                'message' => 'Mark single notification as read.',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $notification = $service->markOneRead($user, $notificationId);

        $this->assertNotNull($notification->read_at);
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    private function seedUnreadNotifications(User $user, int $count): void
    {
        for ($index = 0; $index < $count; $index++) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'test.notification',
                'data' => [
                    'title' => 'Notification ' . ($index + 1),
                    'message' => 'Unread item ' . ($index + 1),
                ],
                'created_at' => now()->subSeconds($count - $index),
                'updated_at' => now()->subSeconds($count - $index),
            ]);
        }
    }
}
