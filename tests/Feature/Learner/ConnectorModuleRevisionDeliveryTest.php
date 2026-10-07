<?php

namespace Tests\Feature\Learner;

use App\Models\ConnectorModulePurchase;
use App\Models\Lesson;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\ContentGovernanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;
use Tests\Feature\Connectors\ConnectorTestHelpers;

class ConnectorModuleRevisionDeliveryTest extends DatabaseTestCase
{
    use DatabaseTransactions;
    use ConnectorTestHelpers;

    public function test_compatible_approved_revision_delivers_to_existing_connector_entitlement_without_repurchase(): void
    {
        Notification::fake();
        [$manager, , $module, , $purchase, $payment] = $this->createConnectorModulePaymentState([
            'max_age' => 100,
        ]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_revision_1');

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        $published = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 1,
            'snapshot_payload' => [
                'module' => ['id' => $module->id, 'title' => 'Published module'],
                'lessons' => [['attributes' => ['id' => $lesson->id, 'title' => 'Original lesson'], 'topics' => []]],
                'quizzes' => [],
            ],
            'submitted_by' => $admin->id,
            'status' => 'approved',
            'submitted_at' => now(),
        ]);
        $module->forceFill([
            'published_revision_id' => $published->id,
            'current_review_status' => ContentGovernanceService::STATUS_IN_REVIEW,
        ])->save();
        $candidate = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 2,
            'snapshot_payload' => [
                'module' => ['id' => $module->id, 'title' => 'Updated module'],
                'lessons' => [['attributes' => ['id' => $lesson->id, 'title' => 'Edited lesson'], 'topics' => []]],
                'quizzes' => [],
            ],
            'submitted_by' => $manager->id,
            'status' => ContentGovernanceService::STATUS_IN_REVIEW,
            'submitted_at' => now(),
        ]);
        $reviewRequest = ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $candidate->id,
            'status' => ContentGovernanceService::STATUS_IN_REVIEW,
            'submitted_by' => $manager->id,
            'submitted_at' => now(),
        ]);

        app(ContentGovernanceService::class)->approveReview($reviewRequest, $admin);

        $this->assertSame($candidate->id, $module->fresh()->published_revision_id);
        $this->assertDatabaseHas('connector_module_entitlements', [
            'id' => $purchase->fresh()->entitlement->id,
            'module_id' => $module->id,
        ]);
        $this->assertSame(1, ConnectorModulePurchase::query()->whereKey($purchase->id)->count());
        $this->assertSame('Updated module', data_get($module->fresh()->publishedSnapshot(), 'module.title'));
    }
}
