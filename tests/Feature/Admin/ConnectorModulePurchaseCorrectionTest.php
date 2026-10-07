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
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ConnectorModulePurchaseCorrectionTest extends TestCase
{
    use RefreshDatabase;
    use ConnectorTestHelpers;

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
}
