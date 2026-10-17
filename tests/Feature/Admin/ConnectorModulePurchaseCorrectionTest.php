<?php

namespace Tests\Feature\Admin;

use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleEntitlement;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ConnectorModulePurchaseCorrectionTest extends TestCase
{
    use ConnectorTestHelpers;
    use RefreshDatabase;

    public function test_void_is_append_only_releases_access_logs_correction_and_preserves_progress(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        [$manager, $connector, $module, , $purchase, $payment] = $this->createConnectorModulePaymentState([
            'max_age' => 100,
        ]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_correct_1');
        $learner = $this->createAdultConnectorMember($connector);
        $access = app(ConnectorModuleAccessService::class);
        $access->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_MEMBER,
            'target_id' => $learner->id,
        ]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        UserProgress::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now()->subHour(),
        ]);
        Notification::fake();

        $this->actingAs($admin)->post(route('admin.monetization.connector-module-purchases.correct', $purchase), [
            'action' => 'void',
            'reason' => 'Duplicate provider charge under manual review.',
        ])->assertRedirect();

        $entitlement = $purchase->fresh()->entitlement;
        $this->assertDatabaseHas('connector_module_entitlements', [
            'id' => $entitlement->id,
            'status' => ConnectorModuleEntitlement::STATUS_VOIDED,
        ]);
        $this->assertDatabaseHas('connector_module_purchases', ['id' => $purchase->id, 'status' => 'corrected']);
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'connector_module_entitlement_id' => $entitlement->id,
            'user_id' => $learner->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'connector_module_purchases.void',
            'entity_id' => $purchase->id,
        ]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'completed' => 1,
        ]);
    }

    public function test_correction_requires_admin_reason(): void
    {
        [$manager, , , , $purchase] = $this->createConnectorModulePaymentState();

        $this->actingAs($manager)->post(route('admin.monetization.connector-module-purchases.correct', $purchase), [
            'action' => 'void',
            'reason' => 'Not allowed.',
        ])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('admin.monetization.connector-module-purchases.correct', $purchase), [
            'action' => 'void',
        ])->assertSessionHasErrors('reason');
    }

    public function test_restore_reconciles_assignments_without_duplicate_purchase_or_ledger(): void
    {
        [$manager, $connector, , , $purchase, $payment] = $this->createConnectorModulePaymentState(['max_age' => 100]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_restore');
        $learner = $this->createAdultConnectorMember($connector);
        app(ConnectorModuleAccessService::class)->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_MEMBER,
            'target_id' => $learner->id,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $route = route('admin.monetization.connector-module-purchases.correct', $purchase);
        $this->actingAs($admin)->post($route, ['action' => 'void', 'reason' => 'Temporary review.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post($route, ['action' => 'restore', 'reason' => 'Review resolved.'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('connector_module_entitlements', [
            'id' => $purchase->fresh()->entitlement->id,
            'status' => ConnectorModuleEntitlement::STATUS_CORRECTED,
        ]);
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'connector_module_entitlement_id' => $purchase->fresh()->entitlement->id,
            'user_id' => $learner->id,
            'status' => 'active',
            'consumes_seat' => true,
        ]);
        $this->assertDatabaseCount('connector_module_purchases', 1);
        $this->assertDatabaseCount('module_sale_ledgers', 1);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'completed']);
        $this->assertDatabaseHas('admin_activity_logs', ['action' => 'connector_module_purchases.restore']);
    }

    #[DataProvider('unsafeRestores')]
    public function test_restore_rejects_unsafe_modules_or_insufficient_capacity(array $attributes, string $error): void
    {
        [, , $module, , $purchase, $payment] = $this->createConnectorModulePaymentState();
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_unsafe_restore');
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $route = route('admin.monetization.connector-module-purchases.correct', $purchase);
        $this->actingAs($admin)->post($route, ['action' => 'void', 'reason' => 'Review.'])->assertRedirect();
        $module->update($attributes);
        $this->post($route, ['action' => 'restore', 'reason' => 'Try restoring.'])->assertSessionHasErrors($error);
        $this->assertSame(ConnectorModuleEntitlement::STATUS_VOIDED, $purchase->fresh()->entitlement->status);
    }

    public static function unsafeRestores(): array
    {
        return [
            'unpublished' => [['is_published' => false], 'purchase'],
            'capacity exhausted' => [['enrollment_limit' => 1], 'capacity'],
        ];
    }

    public function test_retry_finalization_recovers_paid_attention_case_idempotently(): void
    {
        [, , $module, , $purchase, $payment] = $this->createConnectorModulePaymentState(['enrollment_limit' => 1]);
        $purchase->update(['capacity_hold_expires_at' => now()->subMinute()]);
        $payment->update(['status' => 'completed']);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $route = route('admin.monetization.connector-module-purchases.correct', $purchase);
        $this->actingAs($admin)->post($route, ['action' => 'retry_finalization', 'reason' => 'Inspect paid checkout.'])
            ->assertRedirect()->assertSessionHas('warning');
        $this->assertSame('attention_required', $purchase->fresh()->status);
        $this->assertDatabaseCount('connector_module_entitlements', 0);

        $module->update(['enrollment_limit' => 10]);
        $this->post($route, ['action' => 'retry_finalization', 'reason' => 'Capacity restored.'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->post($route, ['action' => 'retry_finalization', 'reason' => 'Verify replay.'])->assertRedirect();
        $this->assertSame('completed', $purchase->fresh()->status);
        $this->assertDatabaseCount('connector_module_entitlements', 1);
        $this->assertDatabaseCount('module_sale_ledgers', 1);
        $this->assertDatabaseCount('connector_module_purchases', 1);
    }
}
