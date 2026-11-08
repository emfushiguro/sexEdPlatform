<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivityLog;
use App\Models\Certificate;
use App\Models\ConnectorModuleEntitlement;
use App\Models\Lesson;
use App\Models\ModulePurchase;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\AdminActivityLogService;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class ConnectorModuleAccessManagementTest extends TestCase
{
    use ConnectorTestHelpers;

    private function workspace(int $seats = 2): array
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        [$manager, $connector, $module, , $purchase, $payment] = $this->createConnectorModulePaymentState(
            ['min_age' => 5, 'max_age' => 100],
            ['seat_capacity' => $seats],
            ['seat_capacity' => $seats],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_admin_access');

        return [$admin, $connector, $module, $purchase->fresh()->entitlement, $manager];
    }

    private function actionUrl($connector, $entitlement, string $action = 'update'): string
    {
        return route('admin.connectors.module-access.'.$action, [$connector, $entitlement]);
    }

    public function test_workspace_lists_unassigned_members_roles_counts_and_filters_without_writes(): void
    {
        [$admin, $connector, $module, $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        $this->actingAs($admin)->get(route('admin.connectors.module-access.index', [
            'connector_id' => $connector->id, 'entitlement_id' => $entitlement->id,
        ]))->assertOk()->assertSee($module->title)->assertSee($member->name)
            ->assertSee('Eligible users')->assertSee('Without access')->assertSee('Review selected users');
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
        $this->get(route('admin.connectors.module-access.index', [
            'connector_id' => $connector->id, 'entitlement_id' => $entitlement->id,
            'search' => 'no-matching-person',
        ]))->assertOk()->assertDontSee($member->email);
    }

    public function test_review_does_not_assign_and_grant_revoke_regrant_are_retryable_and_audited(): void
    {
        [$admin, $connector, $module, $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        $payload = ['action' => 'grant', 'user_ids' => [$member->id]];
        $this->actingAs($admin)->post($this->actionUrl($connector, $entitlement, 'review'), $payload)
            ->assertOk()->assertSee($member->name)->assertSee('Confirm grant');
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
        $url = $this->actionUrl($connector, $entitlement);
        $this->post($url, $payload)->assertSessionHasErrors('confirmed');
        $payload['confirmed'] = '1';
        foreach (['grant', 'grant', 'revoke', 'revoke', 'grant'] as $action) {
            $this->post($url, [...$payload, 'action' => $action])->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('connector_module_seat_assignments', 1);
        $this->assertDatabaseCount('connector_module_access_rules', 1);
        $this->assertDatabaseCount('module_access_grants', 1);
        $this->assertDatabaseCount('module_purchases', 0);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('admin_activity_logs', 3);
        $log = AdminActivityLog::query()->where('action', 'connector_module_access.revoke')->firstOrFail();
        $this->assertSame($admin->id, $log->admin_user_id);
        $this->assertSame($member->id, $log->meta_json['user_id']);
        $this->assertSame($connector->id, $log->meta_json['connector_id']);
        $this->assertSame($module->id, $log->meta_json['module_id']);
        $this->assertTrue(app(ModuleAccessService::class)->hasActiveAccess($member, $module));
    }

    public function test_bulk_capacity_and_foreign_user_failures_leave_no_partial_assignments_or_logs(): void
    {
        [$admin, $connector, , $entitlement] = $this->workspace(1);
        $one = $this->createAdultConnectorMember($connector);
        $two = $this->createAdultConnectorMember($connector);
        $foreign = User::factory()->create();
        $this->actingAs($admin);
        foreach ([[$one->id, $foreign->id], [$one->id, $two->id]] as $ids) {
            $this->post($this->actionUrl($connector, $entitlement), [
                'action' => 'grant', 'user_ids' => $ids, 'confirmed' => '1',
            ])->assertSessionHasErrors('user_ids');
            $this->assertDatabaseCount('connector_module_seat_assignments', 0);
            $this->assertDatabaseCount('connector_module_access_rules', 0);
            $this->assertDatabaseCount('module_access_grants', 0);
            $this->assertDatabaseCount('admin_activity_logs', 0);
        }
    }

    public function test_bulk_success_preserves_existing_seat_and_independent_access(): void
    {
        [$admin, $connector, $module, $entitlement] = $this->workspace(2);
        $one = $this->createAdultConnectorMember($connector);
        $two = $this->createAdultConnectorMember($connector);
        $three = $this->createAdultConnectorMember($connector);
        $url = $this->actionUrl($connector, $entitlement);
        $this->actingAs($admin)->post($url, ['action' => 'grant', 'user_ids' => [$one->id, $two->id], 'confirmed' => '1'])
            ->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'grant', 'user_ids' => [$three->id], 'confirmed' => '1'])->assertSessionHasErrors('user_ids');
        $this->assertTrue(app(ModuleAccessService::class)->hasActiveAccess($one, $module));
        $this->assertTrue(app(ModuleAccessService::class)->hasActiveAccess($two, $module));
        $purchase = ModulePurchase::query()->create([
            'user_id' => $one->id, 'module_id' => $module->id,
            'amount' => 100, 'currency' => 'PHP', 'status' => 'completed', 'purchased_at' => now(),
        ]);
        app(ModuleAccessService::class)->grantIndividualPurchase($purchase);
        $this->post($url, ['action' => 'revoke', 'user_ids' => [$one->id, $two->id], 'confirmed' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue(app(ModuleAccessService::class)->hasActiveAccess($one, $module));
        $this->assertFalse(app(ModuleAccessService::class)->hasActiveAccess($two, $module));
        $this->assertSame('completed', $purchase->fresh()->status);
        $this->post($url, ['action' => 'grant', 'user_ids' => [$three->id], 'confirmed' => '1'])->assertSessionHasNoErrors();
    }

    public function test_admin_routes_and_service_reject_non_admin_and_cross_connector_entitlement(): void
    {
        [$admin, $connector, , $entitlement, $manager] = $this->workspace();
        $other = $this->createVerifiedConnector(User::factory()->create());
        $payload = ['action' => 'grant', 'user_ids' => [$manager->id], 'confirmed' => '1'];
        $this->actingAs($manager)->get(route('admin.connectors.module-access.index'))->assertForbidden();
        foreach (['review', 'update'] as $action) {
            $this->post($this->actionUrl($connector, $entitlement, $action), $payload)->assertForbidden();
            $this->actingAs($admin)->post($this->actionUrl($other, $entitlement, $action), $payload)->assertNotFound();
            $this->actingAs($manager);
        }
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ConnectorModuleAccessService::class)->manageMembers($entitlement, $manager, 'grant', [$manager->id]);
    }

    public function test_grant_revalidates_member_connector_module_and_license_at_confirmation(): void
    {
        [$admin, $connector, $module, $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        $payload = ['action' => 'grant', 'user_ids' => [$member->id], 'confirmed' => '1'];
        $url = $this->actionUrl($connector, $entitlement);
        $this->actingAs($admin);
        $this->post($this->actionUrl($connector, $entitlement, 'review'), $payload)->assertOk();
        $connector->update(['status' => 'suspended']);
        $this->post($url, $payload)->assertSessionHasErrors('entitlement');
        $connector->update(['status' => 'verified']);
        $module->update(['is_published' => false]);
        $this->post($url, $payload)->assertSessionHasErrors('entitlement');
        $module->update(['is_published' => true]);
        $entitlement->update(['status' => ConnectorModuleEntitlement::STATUS_VOIDED]);
        $this->post($url, $payload)->assertSessionHasErrors('entitlement');
        $entitlement->update(['status' => ConnectorModuleEntitlement::STATUS_ACTIVE]);
        $member->update(['status' => User::STATUS_SUSPENDED]);
        $this->post($url, $payload)->assertSessionHasErrors('user_ids');
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
    }

    public function test_validation_after_review_returns_to_get_workspace_with_feedback(): void
    {
        [$admin, $connector, , $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        $connector->memberships()->where('user_id', $member->id)->update(['status' => 'removed']);
        $this->actingAs($admin)->from($this->actionUrl($connector, $entitlement, 'review'))
            ->post($this->actionUrl($connector, $entitlement), [
                'action' => 'grant', 'user_ids' => [$member->id], 'confirmed' => '1',
            ])->assertRedirect(route('admin.connectors.module-access.index', [
                'connector_id' => $connector->id, 'entitlement_id' => $entitlement->id,
            ]))->assertSessionHasErrors('user_ids');
    }

    public function test_revoke_preserves_learning_records_and_remains_available_after_license_suspension(): void
    {
        [$admin, $connector, $module, $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        $url = $this->actionUrl($connector, $entitlement);
        $this->actingAs($admin)->post($url, ['action' => 'grant', 'user_ids' => [$member->id], 'confirmed' => '1'])->assertSessionHasNoErrors();
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_published' => true]);
        $progress = UserProgress::query()->create([
            'user_id' => $member->id, 'module_id' => $module->id, 'lesson_id' => $lesson->id,
            'completed' => true, 'progress_percentage' => 100, 'completed_at' => now(),
        ]);
        $quiz = Quiz::factory()->create(['module_id' => $module->id]);
        $attempt = QuizAttempt::query()->create([
            'user_id' => $member->id, 'quiz_id' => $quiz->id, 'answers' => [],
            'score' => 100, 'passed' => true, 'started_at' => now(), 'completed_at' => now(),
        ]);
        $certificate = Certificate::query()->create(['user_id' => $member->id, 'module_id' => $module->id]);
        $enrollment = $member->moduleEnrollments()->where('module_id', $module->id)->firstOrFail();
        $enrollment->update(['completion_percentage' => 100]);
        $snapshots = [$progress->fresh()->toArray(), $attempt->fresh()->toArray(), $certificate->fresh()->toArray()];
        $connector->memberships()->where('user_id', $member->id)->update(['status' => 'removed']);
        $entitlement->update(['status' => 'suspended']);
        $this->post($url, ['action' => 'revoke', 'user_ids' => [$member->id], 'confirmed' => '1'])->assertSessionHasNoErrors();
        $this->assertSame($snapshots, [$progress->fresh()->toArray(), $attempt->fresh()->toArray(), $certificate->fresh()->toArray()]);
        $this->assertSame(100, (int) $enrollment->fresh()->completion_percentage);
        $this->actingAs($member)->get(route('learner.lessons.show', $lesson))->assertForbidden();
        $this->get(route('learner.certificates.show', $certificate))->assertOk();
    }

    public function test_audit_failure_rolls_back_every_member(): void
    {
        [$admin, $connector, , $entitlement] = $this->workspace();
        $one = $this->createAdultConnectorMember($connector);
        $two = $this->createAdultConnectorMember($connector);
        $this->mock(AdminActivityLogService::class, function ($mock): void {
            $mock->shouldReceive('logModelMutation')->once()->andThrow(new \RuntimeException('Audit storage failed'));
        });
        try {
            app(ConnectorModuleAccessService::class)->manageMembers($entitlement, $admin, 'grant', [$one->id, $two->id]);
            $this->fail('The audit error must abort the operation.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Audit storage failed', $error->getMessage());
        }
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
        $this->assertDatabaseCount('connector_module_access_rules', 0);
        $this->assertDatabaseCount('module_access_grants', 0);
        $this->assertDatabaseCount('admin_activity_logs', 0);
    }

    public function test_guardian_pending_reserves_seat_and_staff_role_can_receive_a_grant(): void
    {
        [$admin, $connector, , $entitlement] = $this->workspace(1);
        $minor = $this->createMinorLearner(10);
        $role = $this->createCustomRole($connector);
        $role->update(['name' => 'Staff']);
        $connector->memberships()->create(['user_id' => $minor->id, 'connector_role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($admin)->post($this->actionUrl($connector, $entitlement), [
            'action' => 'grant', 'user_ids' => [$minor->id], 'confirmed' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'user_id' => $minor->id, 'status' => 'pending_guardian', 'consumes_seat' => true,
        ]);
        $this->get(route('admin.connectors.module-access.index', [
            'connector_id' => $connector->id, 'entitlement_id' => $entitlement->id, 'role_id' => $role->id, 'access' => 'pending',
        ]))->assertOk()->assertSee($minor->email)->assertSee('Staff');
    }

    public function test_archived_module_keeps_roster_and_revocation_available(): void
    {
        [$admin, $connector, $module, $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        $url = $this->actionUrl($connector, $entitlement);
        $this->actingAs($admin)->post($url, ['action' => 'grant', 'user_ids' => [$member->id], 'confirmed' => '1'])->assertSessionHasNoErrors();
        $module->delete();
        $this->get(route('admin.connectors.module-access.index', [
            'connector_id' => $connector->id, 'entitlement_id' => $entitlement->id,
        ]))->assertOk()->assertSee($member->name)->assertSee('Unavailable module');
        $this->post($url, ['action' => 'grant', 'user_ids' => [$member->id], 'confirmed' => '1'])->assertSessionHasErrors('entitlement');
        $this->post($url, ['action' => 'revoke', 'user_ids' => [$member->id], 'confirmed' => '1'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('connector_module_seat_assignments', ['user_id' => $member->id, 'status' => 'revoked']);
    }

    public function test_bulk_revoke_rejects_foreign_and_unassigned_users_without_revoking_valid_member(): void
    {
        [$admin, $connector, , $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        $unassigned = $this->createAdultConnectorMember($connector);
        $foreign = User::factory()->create();
        $url = $this->actionUrl($connector, $entitlement);
        $this->actingAs($admin)->post($url, ['action' => 'grant', 'user_ids' => [$member->id], 'confirmed' => '1'])->assertSessionHasNoErrors();
        foreach ([$unassigned->id, $foreign->id] as $invalid) {
            $this->post($url, ['action' => 'revoke', 'user_ids' => [$member->id, $invalid], 'confirmed' => '1'])->assertSessionHasErrors('user_ids');
            $this->assertDatabaseHas('connector_module_seat_assignments', ['user_id' => $member->id, 'status' => 'active']);
            $this->assertDatabaseCount('connector_module_seat_assignments', 1);
        }
        $this->assertDatabaseCount('admin_activity_logs', 1);
    }

    public function test_roster_uses_shared_access_rules_for_individual_other_connector_and_legacy_sources(): void
    {
        [$admin, $connector, $module, $entitlement] = $this->workspace();
        $individual = $this->createAdultConnectorMember($connector);
        $otherMember = $this->createAdultConnectorMember($connector);
        $legacy = $this->createAdultConnectorMember($connector);
        $individualPurchase = ModulePurchase::query()->create([
            'user_id' => $individual->id, 'module_id' => $module->id, 'amount' => 100,
            'currency' => 'PHP', 'status' => 'completed', 'purchased_at' => now(),
        ]);
        app(ModuleAccessService::class)->grantIndividualPurchase($individualPurchase);
        $legacy->moduleEnrollments()->create(['module_id' => $module->id, 'status' => 'approved']);
        $owner = User::factory()->create();
        $other = $this->createVerifiedConnector($owner);
        $other->memberships()->create([
            'user_id' => $otherMember->id, 'connector_role_id' => $other->roles->first()->id, 'status' => 'active',
        ]);
        $purchase = $entitlement->purchase->replicate()->fill(['connector_id' => $other->id, 'purchased_by' => $owner->id, 'payment_id' => null]);
        $purchase->save();
        $otherEntitlement = $entitlement->replicate()->fill(['connector_id' => $other->id, 'connector_module_purchase_id' => $purchase->id]);
        $otherEntitlement->save();
        app(ConnectorModuleAccessService::class)->storeRule($otherEntitlement, $owner, ['target_type' => 'member', 'target_id' => $otherMember->id]);
        $individual->update(['birthdate' => now()->subYears(110)->toDateString()]);
        $service = app(ModuleAccessService::class);
        $roster = app(ConnectorModuleAccessService::class)->managementRoster($entitlement)->keyBy(fn ($row) => $row['user']->id);
        foreach ([$individual, $otherMember, $legacy] as $member) {
            $this->assertTrue($service->hasActiveAccess($member->fresh(), $module));
            $this->assertTrue($roster[$member->id]['hasAccess']);
        }
        $this->assertFalse($roster[$individual->id]['canGrant']);
        $this->assertTrue($roster[$otherMember->id]['hasOtherConnector']);
    }

    public function test_unexpected_failure_returns_feedback_without_partial_changes(): void
    {
        [$admin, $connector, , $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        $this->mock(AdminActivityLogService::class, function ($mock): void {
            $mock->shouldReceive('logModelMutation')->once()->andThrow(new \RuntimeException('Audit storage failed'));
        });
        $this->actingAs($admin)->post($this->actionUrl($connector, $entitlement), [
            'action' => 'grant', 'user_ids' => [$member->id], 'confirmed' => '1',
        ])->assertRedirect(route('admin.connectors.module-access.index', [
            'connector_id' => $connector->id, 'entitlement_id' => $entitlement->id,
        ]))->assertSessionHasErrors('operation');
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
        $this->assertDatabaseCount('module_access_grants', 0);
    }

    public function test_individual_purchase_does_not_consume_a_seat_when_connector_capacity_is_full(): void
    {
        [$admin, $connector, $module, $entitlement] = $this->workspace(1);
        $one = $this->createAdultConnectorMember($connector);
        $independent = $this->createAdultConnectorMember($connector);
        $purchase = ModulePurchase::query()->create([
            'user_id' => $independent->id, 'module_id' => $module->id, 'amount' => 100,
            'currency' => 'PHP', 'status' => 'completed', 'purchased_at' => now(),
        ]);
        app(ModuleAccessService::class)->grantIndividualPurchase($purchase);
        $this->actingAs($admin)->post($this->actionUrl($connector, $entitlement), [
            'action' => 'grant', 'user_ids' => [$one->id, $independent->id], 'confirmed' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $entitlement->assignments()->where('consumes_seat', true)->whereNull('released_at')->count());
        $this->assertDatabaseHas('connector_module_seat_assignments', [
            'user_id' => $independent->id, 'status' => 'independent', 'consumes_seat' => false,
        ]);
        $this->assertTrue(app(ModuleAccessService::class)->hasActiveAccess($independent, $module));
    }

    public function test_roster_query_count_does_not_grow_per_member(): void
    {
        [$admin, $connector, , $entitlement] = $this->workspace();
        $member = $this->createAdultConnectorMember($connector);
        app(ConnectorModuleAccessService::class)->manageMembers($entitlement, $admin, 'grant', [$member->id]);
        \Illuminate\Support\Facades\DB::enableQueryLog();
        app(ConnectorModuleAccessService::class)->managementRoster($entitlement->fresh());
        $baseline = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();
        for ($i = 0; $i < 5; $i++) {
            $this->createAdultConnectorMember($connector);
        }
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        app(ConnectorModuleAccessService::class)->managementRoster($entitlement->fresh());
        $this->assertSame($baseline, count(\Illuminate\Support\Facades\DB::getQueryLog()));
        \Illuminate\Support\Facades\DB::disableQueryLog();
    }
}
