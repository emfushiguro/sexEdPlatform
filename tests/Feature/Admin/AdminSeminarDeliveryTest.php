<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\Seminars\SeminarDeliveryNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class AdminSeminarDeliveryTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_admin_can_change_only_delivery_fields_and_organizer_gets_one_notice(): void
    {
        Notification::fake();
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $seminar = $connector->seminars()->create([
            'title' => 'External class', 'description' => 'Description', 'type' => 'seminar',
            'event_format' => 'external', 'status' => 'completed', 'external_url' => 'https://zoom.example.test/old',
            'external_link_expiry_mode' => 'ongoing', 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHour(),
        ]);
        $seminar->registrants()->create(['user_id' => $owner->id, 'status' => 'registered', 'registered_at' => now()]);

        $this->actingAs($admin)->put(route('admin.seminars.delivery.update', $seminar), [
            'external_url' => 'https://zoom.example.test/new', 'external_link_expiry_mode' => 'ongoing',
            'title' => 'Injected title', 'description' => 'Injected description',
        ])->assertRedirect();
        $this->assertSame('External class', $seminar->fresh()->title);
        $this->assertSame('Description', $seminar->fresh()->description);
        Notification::assertSentToTimes($owner, SeminarDeliveryNotification::class, 1);
        $this->assertStringNotContainsString('https://zoom.example.test/new', json_encode(Notification::sent($owner, SeminarDeliveryNotification::class)->first()->toDatabase($owner)));
    }

    public function test_non_admin_cannot_use_admin_delivery_action(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create(['title' => 'External class', 'event_format' => 'external', 'status' => 'published']);
        $this->actingAs($owner)->put(route('admin.seminars.delivery.update', $seminar), [
            'external_url' => 'https://zoom.example.test/new', 'external_link_expiry_mode' => 'ongoing',
        ])->assertForbidden();
    }
}
