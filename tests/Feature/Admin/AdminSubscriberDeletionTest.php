<?php

namespace Tests\Feature\Admin;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminSubscriberDeletionTest extends TestCase
{
    public function test_active_subscriber_is_protected_from_permanent_deletion(): void
    {
        $subscription = $this->subscriber(SubscriptionStatus::Active);

        $this->actingAs($this->admin())->from(route('admin.subscribers.index'))
            ->delete(route('admin.subscribers.destroy', $subscription))
            ->assertRedirect(route('admin.subscribers.index'))
            ->assertSessionHas('error', 'Active subscriptions cannot be permanently deleted.');

        $this->assertDatabaseHas('subscribers', ['id' => $subscription->id, 'status' => 'active', 'deleted_at' => null]);
    }

    public static function deletableStatuses(): array
    {
        return [[SubscriptionStatus::Cancelled], [SubscriptionStatus::Expired]];
    }

    #[DataProvider('deletableStatuses')]
    public function test_non_active_subscriber_can_be_deleted_without_deleting_the_user(SubscriptionStatus $status): void
    {
        $subscription = $this->subscriber($status);

        $this->actingAs($this->admin())->from(route('admin.subscribers.index'))
            ->delete(route('admin.subscribers.destroy', $subscription))
            ->assertRedirect(route('admin.subscribers.index'))
            ->assertSessionHas('success', 'Subscriber record permanently deleted.');

        $this->assertDatabaseMissing('subscribers', ['id' => $subscription->id]);
        $this->assertDatabaseHas('users', ['id' => $subscription->user_id, 'deleted_at' => null]);
    }

    public function test_non_admin_cannot_delete_a_subscriber(): void
    {
        $subscription = $this->subscriber(SubscriptionStatus::Expired);
        $learner = $subscription->user;
        $learner->assignRole('learner');

        $this->actingAs($learner)->delete(route('admin.subscribers.destroy', $subscription))->assertForbidden();

        $this->assertDatabaseHas('subscribers', ['id' => $subscription->id, 'deleted_at' => null]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function subscriber(SubscriptionStatus $status): Subscription
    {
        return Subscription::create([
            'user_id' => User::factory()->create(['role' => 'learner'])->id,
            'plan' => 'premium', 'status' => $status,
            'start_date' => now()->subMonth(), 'end_date' => $status === SubscriptionStatus::Active ? now()->addMonth() : now()->subDay(),
            'price_paid' => 199, 'auto_renew' => false,
        ]);
    }
}
