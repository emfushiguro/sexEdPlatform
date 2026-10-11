<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\ModulePurchase;
use App\Models\Payment;
use App\Models\User;
use App\Services\ModulePurchaseService;
use App\Services\PayMongoPaymentLinkService;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use Tests\TestCase;

class SharedModuleCapacityTest extends TestCase
{
    public function test_individual_checkout_creates_an_expiring_capacity_hold(): void
    {
        Config::set('billing.module_marketplace.capacity_hold_minutes', 30);
        $learner = User::factory()->create(['role' => 'learner']);
        $admin = User::factory()->create(['role' => 'admin']);
        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'access_type' => 'paid',
            'price_amount' => 2500,
            'price_currency' => 'PHP',
        ]);

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_capacity_hold',
                        'attributes' => ['checkout_url' => 'https://checkout.test/capacity-hold'],
                    ],
                ]);
        });

        $result = app(ModulePurchaseService::class)->createCheckout($learner, $module, 'gcash');
        $purchase = ModulePurchase::query()
            ->where('user_id', $learner->id)
            ->where('module_id', $module->id)
            ->firstOrFail();

        $this->assertSame('checkout_created', $result['status']);
        $this->assertNotNull($purchase->capacity_hold_expires_at);
        $this->assertTrue($purchase->capacity_hold_expires_at->isFuture());
        $this->assertDatabaseHas('payments', [
            'user_id' => $learner->id,
            'status' => PaymentStatus::Pending->value,
        ]);
    }
}
