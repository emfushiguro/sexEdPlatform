<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleAccessRule;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Notifications\Connectors\ConnectorModuleAccessNotification;
use App\Notifications\Connectors\ConnectorModulePurchaseNotification;
use App\Notifications\Instructor\ModuleMarketplaceStatusNotification;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Modules\ModuleMarketplaceListingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConnectorModuleNotificationTest extends TestCase
{
    use RefreshDatabase;
    use ConnectorTestHelpers;

    public function test_completed_purchase_notifies_buyer_after_commit(): void
    {
        [$manager, , , , , $payment] = $this->createConnectorModulePaymentState();
        Notification::fake();
        $this->assertTrue(app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_notify_1'));
        Notification::assertSentTo($manager, ConnectorModulePurchaseNotification::class, function (ConnectorModulePurchaseNotification $notification): bool {
            return $notification->status === 'completed';
        });
    }

    public function test_attention_required_purchase_notifies_buyer_and_platform_admin_after_commit(): void
    {
        [$manager, , $module, , , $payment] = $this->createConnectorModulePaymentState();
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $module->forceFill(['is_published' => false])->save();
        Notification::fake();
        $this->assertFalse(app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_notify_2'));
        Notification::assertSentTo($manager, ConnectorModulePurchaseNotification::class);
        Notification::assertSentTo($admin, ConnectorModulePurchaseNotification::class);
    }

    public function test_assignment_activation_and_revocation_notify_learner_after_commit(): void
    {
        [$manager, $connector, , , $purchase, $payment] = $this->createConnectorModulePaymentState([
            'max_age' => 100,
        ]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_notify_3');
        $learner = $this->createAdultConnectorMember($connector);
        Notification::fake();
        $access = app(ConnectorModuleAccessService::class);
        $access->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_MEMBER,
            'target_id' => $learner->id,
        ]);
        Notification::assertSentTo($learner, ConnectorModuleAccessNotification::class, 1);
        $access->revoke($purchase->fresh()->entitlement, $learner, $manager, 'manual_review');
        Notification::assertSentTo($learner, ConnectorModuleAccessNotification::class, 2);
    }

    public function test_instructor_listing_suspension_notifies_seller_after_commit(): void
    {
        $seller = \App\Models\User::factory()->create(['role' => 'instructor']);
        $seller->assignRole('instructor');
        $module = Module::factory()->create([
            'created_by' => $seller->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => 'approved',
        ]);
        $listing = ModuleMarketplaceListing::query()->create([
            'module_id' => $module->id,
            'seller_type' => 'instructor',
            'seller_user_id' => $seller->id,
            'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
            'price_amount' => 2500,
            'currency' => 'PHP',
            'seat_capacity' => 8,
        ]);
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        Notification::fake();

        app(ModuleMarketplaceListingService::class)->suspend($listing, $admin, 'Review required');

        Notification::assertSentTo($seller, ModuleMarketplaceStatusNotification::class, function (ModuleMarketplaceStatusNotification $notification): bool {
            return $notification->status === ModuleMarketplaceListing::STATUS_SUSPENDED
                && $notification->reason === 'Review required';
        });
    }
}
