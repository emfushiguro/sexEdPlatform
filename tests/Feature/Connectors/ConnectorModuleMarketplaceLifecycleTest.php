<?php

namespace Tests\Feature\Connectors;

use App\Enums\PaymentStatus;
use App\Http\Middleware\VerifyPayMongoWebhook;
use App\Models\Certificate;
use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModulePurchase;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\FeatureCatalog;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleMarketplaceListing;
use App\Models\Payment;
use App\Models\PlanFeatureEntitlement;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\PayMongoPaymentLinkService;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Tests\TestCase;

class ConnectorModuleMarketplaceLifecycleTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_marketplace_lifecycle_preserves_history_and_reassigns_seat(): void
    {
        Notification::fake();

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $this->createPaidDistributionPlan();

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => true,
            'current_review_status' => 'approved',
            'min_age' => 5,
            'max_age' => 100,
        ]);

        $this->actingAs($instructor)
            ->put(route('instructor.modules.marketplace.update', $module), [
                'price_amount' => 2500,
                'currency' => config('billing.currency.code'),
                'seat_capacity' => 1,
                'action' => 'publish',
            ])
            ->assertRedirect();

        $listing = $module->marketplaceListing()->firstOrFail();
        $manager = User::factory()->create();
        $connector = $this->createVerifiedConnector($manager);
        $member = $this->createAdultConnectorMember($connector);

        $this->actingAs($member)
            ->get(route('connector.modules', $connector))
            ->assertOk()
            ->assertSee($module->title)
            ->assertSee('1 seats')
            ->assertDontSee('Purchase for Connector');

        $this->actingAs($manager)
            ->get(route('connector.modules', $connector))
            ->assertOk()
            ->assertSee('Purchase for Connector');

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_connector_marketplace_lifecycle',
                        'attributes' => ['checkout_url' => 'https://checkout.example.test/lifecycle'],
                    ],
                ]);
        });

        $this->actingAs($member)
            ->post(route('connector.modules.purchase.store', [$connector, $listing]), [
                'payment_method' => 'gcash',
                'accept_terms' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('connector.modules.purchase.store', [$connector, $listing]), [
                'payment_method' => 'gcash',
                'accept_terms' => '1',
            ])
            ->assertRedirect('https://checkout.example.test/lifecycle');

        $purchase = ConnectorModulePurchase::query()
            ->where('connector_id', $connector->id)
            ->latest('id')
            ->firstOrFail();
        $payment = Payment::query()->findOrFail($purchase->payment_id);

        $this->assertSame(ConnectorModulePurchase::STATUS_PENDING, $purchase->status);
        $this->assertSame(PaymentStatus::Pending, $payment->status);

        $this->withoutMiddleware(VerifyPayMongoWebhook::class);
        $payload = [
            'data' => [
                'id' => 'evt_connector_marketplace_lifecycle',
                'attributes' => [
                    'type' => 'link.payment.paid',
                    'data' => [
                        'id' => 'pay_connector_marketplace_lifecycle',
                        'attributes' => [
                            'amount' => 250000,
                            'source' => ['type' => 'gcash'],
                            'metadata' => [
                                'payment_scope' => 'connector_module_purchase',
                                'connector_id' => $connector->id,
                                'connector_module_purchase_id' => $purchase->id,
                                'payment_id' => $payment->id,
                                'user_id' => $manager->id,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson(route('webhook.paymongo'), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $purchase->refresh();
        $entitlement = $purchase->entitlement()->firstOrFail();
        $this->assertSame(ConnectorModulePurchase::STATUS_COMPLETED, $purchase->status);
        $this->assertSame('active', $entitlement->status);

        $this->actingAs($manager)
            ->get(route('connector.modules.purchase.confirmation', [$connector, $purchase]))
            ->assertOk()
            ->assertSee('View Learning Library');

        $access = app(ConnectorModuleAccessService::class);
        $access->storeRule($entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_MEMBER,
            'target_id' => $member->id,
        ]);

        $assignment = ConnectorModuleSeatAssignment::query()
            ->where('connector_module_entitlement_id', $entitlement->id)
            ->where('user_id', $member->id)
            ->firstOrFail();
        $this->assertSame(ConnectorModuleSeatAssignment::STATUS_ACTIVE, $assignment->status);
        $this->assertTrue((bool) $assignment->consumes_seat);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
            'order' => 1,
            'text_content' => '<p>Connector marketplace learning content.</p>',
        ]);
        $finalQuiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => null,
            'attempt_limit' => 3,
        ]);
        $module->update(['final_quiz_id' => $finalQuiz->id]);
        $module->refresh();

        $this->actingAs($member)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk();

        $this->actingAs($member)
            ->post(route('learner.lessons.complete', $lesson))
            ->assertRedirect();

        $this->actingAs($member)
            ->post(route('learner.topics.complete', $topic))
            ->assertRedirect();

        QuizAttempt::query()->create([
            'user_id' => $member->id,
            'quiz_id' => $finalQuiz->id,
            'answers' => [],
            'score' => 100,
            'passed' => true,
            'started_at' => now()->subMinutes(2),
            'completed_at' => now()->subMinute(),
        ]);

        $this->actingAs($member)
            ->get(route('learner.modules.completion', $module))
            ->assertOk();

        $this->actingAs($member)
            ->post(route('learner.certificates.check', $module))
            ->assertRedirect();

        $certificate = Certificate::query()
            ->where('user_id', $member->id)
            ->where('module_id', $module->id)
            ->firstOrFail();

        $this->actingAs($member)
            ->get(route('learner.certificates.show', $certificate))
            ->assertOk()
            ->assertSee($certificate->certificate_number);

        $this->actingAs($instructor)
            ->delete(route('instructor.modules.marketplace.destroy', $module))
            ->assertRedirect();

        $this->assertDatabaseHas('module_marketplace_listings', [
            'id' => $listing->id,
            'status' => ModuleMarketplaceListing::STATUS_UNPUBLISHED,
        ]);

        $this->actingAs($manager)
            ->get(route('connector.modules', $connector))
            ->assertOk()
            ->assertDontSee($module->title);

        $this->actingAs($manager)
            ->get(route('connector.modules.marketplace.show', [$connector, $listing]))
            ->assertNotFound();

        $access->revoke($entitlement, $member, $manager, 'manual_revocation');

        $this->actingAs($member)
            ->get(route('learner.lessons.show', $lesson))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('learner.modules.show', $module))
            ->assertOk()
            ->assertSee('Past access');

        $this->actingAs($member)
            ->get(route('learner.certificates.show', $certificate))
            ->assertOk()
            ->assertSee($certificate->certificate_number);

        $this->actingAs($member)
            ->post(route('learner.certificates.check', $module))
            ->assertForbidden();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('connector_module_purchases', [
            'id' => $purchase->id,
            'status' => ConnectorModulePurchase::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('connector_module_entitlements', [
            'id' => $entitlement->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('module_sale_ledgers', [
            'payment_id' => $payment->id,
            'sale_channel' => 'connector_marketplace',
            'connector_id' => $connector->id,
            'module_id' => $module->id,
        ]);
        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $member->id,
            'quiz_id' => $finalQuiz->id,
            'passed' => 1,
        ]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $member->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => 1,
        ]);
        $this->assertDatabaseHas('lesson_topic_progress', [
            'user_id' => $member->id,
            'lesson_topic_id' => $topic->id,
            'completed' => 1,
        ]);
        $this->assertDatabaseHas('module_enrollments', [
            'user_id' => $member->id,
            'module_id' => $module->id,
            'status' => 'inactive',
            'completion_percentage' => 100,
        ]);
        $this->assertDatabaseHas('certificates', ['id' => $certificate->id]);
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'id' => $assignment->id,
            'status' => ConnectorModuleSeatAssignment::STATUS_REVOKED,
            'consumes_seat' => 0,
        ]);

        $replacement = $this->createAdultConnectorMember($connector);
        $access->storeRule($entitlement->fresh(), $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_MEMBER,
            'target_id' => $replacement->id,
        ]);

        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'connector_module_entitlement_id' => $entitlement->id,
            'user_id' => $replacement->id,
            'status' => ConnectorModuleSeatAssignment::STATUS_ACTIVE,
            'consumes_seat' => 1,
        ]);
    }

    private function createPaidDistributionPlan(): SubscriptionPlan
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Marketplace Lifecycle Instructor Plan',
            'slug' => 'marketplace-lifecycle-'.uniqid(),
            'description' => 'Connector marketplace lifecycle test plan',
            'price' => 0,
            'features' => [],
            'plan_audience' => 'instructor',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        foreach ([
            'instructor_can_publish_paid_modules',
            'instructor_can_receive_paid_enrollments',
        ] as $key) {
            $feature = FeatureCatalog::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'value_type' => 'boolean',
                    'category' => 'instructor',
                    'is_active' => true,
                ],
            );
            PlanFeatureEntitlement::query()->create([
                'plan_id' => $plan->id,
                'feature_id' => $feature->id,
                'is_enabled' => true,
                'is_unlimited' => true,
            ]);
        }

        return $plan;
    }
}
