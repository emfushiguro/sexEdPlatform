<?php

namespace Tests\Feature\Connectors;

use App\Models\ConnectorModuleAccessRule;
use App\Models\Module;
use App\Models\User;
use App\Services\Connectors\ConnectorModuleAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Tests\TestCase;

class ConnectorModuleAccessManagementUiTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_access_page_is_manager_only_and_lists_rules_and_assignments(): void
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState(
            moduleOverrides: ['min_age' => 5, 'max_age' => 100],
        );
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_library_2');
        $member = $this->createAdultConnectorMember($connector);
        app(ConnectorModuleAccessService::class)->storeRule($purchase->fresh()->entitlement, $manager, [
            'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
            'target_id' => null,
        ]);

        $this->actingAs($manager)
            ->get(route('connector.modules.library.access', [$connector, $purchase->fresh()->entitlement]))
            ->assertOk()
            ->assertSee('Access Management')
            ->assertSee('All members')
            ->assertSee($member->name)
            ->assertSee('Grant Access')
            ->assertSee('View')
            ->assertSee('Eligibility')
            ->assertDontSee('Add Rule');

        $this->actingAs($member)
            ->get(route('connector.modules.library.access', [$connector, $purchase->fresh()->entitlement]))
            ->assertForbidden();
    }

    public function test_roster_includes_unassigned_teens_with_photo_details_and_age_eligibility(): void
    {
        [$manager, $connector, $module, , $purchase, $payment] = $this->createConnectorModulePaymentState(['min_age' => 13, 'max_age' => 17]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_roster');
        $module->syncLearnerCategories(['teens']);
        $teen = $this->createMinorLearner(15);
        $teen->learnerProfile->update(['avatar_path' => 'avatars/access-teen.png']);
        $connector->memberships()->create(['user_id' => $teen->id, 'connector_role_id' => $connector->roles->first()->id, 'status' => 'active']);
        $adult = $this->createAdultConnectorMember($connector);

        $this->actingAs($manager)->get(route('connector.modules.library.access', [$connector, $purchase->fresh()->entitlement]))
            ->assertOk()->assertSee($teen->name)->assertSee($adult->name)
            ->assertSee('avatars/access-teen.png')->assertSee('Learner information')->assertSee('Teens')
            ->assertSee('Eligible')->assertSee('Module age group does not match')->assertSee('View');
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
    }

    public function test_member_actions_grant_revoke_restore_and_retry_without_duplicate_seats(): void
    {
        [$manager, $connector, , , $purchase, $payment] = $this->createConnectorModulePaymentState(['min_age' => 5, 'max_age' => 100]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_actions');
        $member = $this->createAdultConnectorMember($connector);
        $url = route('connector.modules.access.member', [$connector, $purchase->fresh()->entitlement]);
        $this->actingAs($manager)->post($url, ['user_id' => $member->id, 'action' => 'grant'])->assertSessionHasErrors('confirmed');
        foreach (['grant', 'grant', 'revoke', 'revoke', 'grant'] as $action) {
            $this->post($url, ['user_id' => $member->id, 'action' => $action, 'confirmed' => '1'])
                ->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('connector_module_seat_assignments', 1);
        $this->assertDatabaseCount('connector_module_access_rules', 1);
        $this->assertDatabaseCount('module_access_grants', 1);
        $this->assertDatabaseCount('module_purchases', 0);
        $this->assertDatabaseHas('connector_module_seat_assignments', ['user_id' => $member->id, 'status' => 'active']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $manager->id, 'activity_type' => 'connector_module_access.grant']);
    }

    public function test_member_actions_reject_outsiders_age_mismatch_and_missing_permission(): void
    {
        [$manager, $connector, $module, , $purchase, $payment] = $this->createConnectorModulePaymentState(['min_age' => 5, 'max_age' => 100]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_eligibility');
        // The legacy broad age range must not override explicit Kids/Adults categories.
        $module->learnerCategories()->createMany([['category' => 'kids'], ['category' => 'adults']]);
        $teen = $this->createMinorLearner(15);
        $connector->memberships()->create(['user_id' => $teen->id, 'connector_role_id' => $connector->roles->first()->id, 'status' => 'active']);
        $member = $this->createAdultConnectorMember($connector);
        $foreign = User::factory()->create();
        $url = route('connector.modules.access.member', [$connector, $purchase->fresh()->entitlement]);
        $this->actingAs($member)->post($url, ['user_id' => $member->id, 'action' => 'grant', 'confirmed' => '1'])->assertForbidden();
        foreach ([$teen->id, $foreign->id] as $id) {
            $this->actingAs($manager)->post($url, ['user_id' => $id, 'action' => 'grant', 'confirmed' => '1'])->assertSessionHasErrors('user_ids');
        }
        $this->actingAs($manager)->post($url, ['user_id' => $foreign->id, 'action' => 'revoke', 'confirmed' => '1'])->assertSessionHasErrors('user_ids');
        $this->assertDatabaseCount('connector_module_seat_assignments', 0);
    }

    public function test_age_category_matches_normal_module_access_and_uses_loaded_categories(): void
    {
        $module = Module::factory()->create(['min_age' => 5, 'max_age' => 100]);
        $module->learnerCategories()->createMany([['category' => 'kids'], ['category' => 'adults']]);
        $module->load('learnerCategories');
        $module->syncLearnerCategories(['teens']);
        $this->assertTrue($module->isAppropriateForAge(15));
        $this->assertFalse($module->isAppropriateForAge(10));
    }

    public function test_unknown_age_details_do_not_query_module_categories_per_member(): void
    {
        [$manager, $connector, , , $purchase, $payment] = $this->createConnectorModulePaymentState();
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_unknown_age');
        for ($i = 0; $i < 3; $i++) {
            $this->createAdultConnectorMember($connector)->update(['birthdate' => null]);
        }
        $entitlement = $purchase->fresh()->entitlement;
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $roster = app(ConnectorModuleAccessService::class)->managementRoster($entitlement);
        foreach ($roster as $row) {
            $entitlement->module->learnerCategoryLabels();
        }
        $queries = collect(\Illuminate\Support\Facades\DB::getQueryLog())->where(fn ($query) => str_contains($query['query'], 'module_learner_categories'));
        \Illuminate\Support\Facades\DB::disableQueryLog();
        $this->assertCount(1, $queries);
    }

    public function test_member_action_failure_shows_reload_feedback(): void
    {
        [$manager, $connector, , , $purchase, $payment] = $this->createConnectorModulePaymentState();
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_action_failure');
        $member = $this->createAdultConnectorMember($connector);
        $this->mock(ConnectorModuleAccessService::class, function ($mock): void {
            $mock->shouldReceive('manageMember')->once()->andThrow(new \RuntimeException('Access write failed'));
        });
        $this->actingAs($manager)->post(route('connector.modules.access.member', [$connector, $purchase->fresh()->entitlement]), [
            'action' => 'grant', 'user_id' => $member->id, 'confirmed' => '1',
        ])->assertRedirect(route('connector.modules.library.access', [$connector, $purchase->fresh()->entitlement]))
            ->assertSessionHasErrors('operation');
    }

    public function test_row_actions_modal_owns_view_and_access_actions_and_grant_removal_requires_review(): void
    {
        [$manager, $connector, , , $purchase, $payment] = $this->createConnectorModulePaymentState(['min_age' => 5, 'max_age' => 100]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_action_modal');
        $member = $this->createAdultConnectorMember($connector);
        $entitlement = $purchase->fresh()->entitlement;
        $url = route('connector.modules.library.access', [$connector, $entitlement]);

        foreach (['Grant Access', 'Revoke Access'] as $expectedAction) {
            $html = $this->actingAs($manager)->get($url)->assertOk()->getContent();
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $row = $xpath->query('//tr[@data-module-member="'.$member->id.'"]')->item(0);
            $this->assertSame(0, $xpath->query('./td[1]//button[@aria-haspopup="dialog"]', $row)->length, 'View must leave the member column.');
            $this->assertSame(1, $xpath->query('./td[last()]/button[@aria-controls="access-actions-'.$member->id.'"]', $row)->length);
            $actions = $xpath->query('//dialog[@id="access-actions-'.$member->id.'"]')->item(0);
            $this->assertNotNull($actions);
            $this->assertStringContainsString('View learner information', $actions->textContent);
            $this->assertStringContainsString($expectedAction, $actions->textContent);
            if ($expectedAction === 'Grant Access') {
                app(ConnectorModuleAccessService::class)->manageMember($entitlement, $manager, 'grant', $member->id);
            }
        }
        $rule = $entitlement->rules()->firstOrFail();
        $removeUrl = route('connector.modules.access-rules.destroy', [$connector, $entitlement, $rule]);
        $this->assertSame(1, $xpath->query('//dialog//form[@action="'.$removeUrl.'"]')->length, 'Removing an existing grant must open a confirmation dialog.');
    }
}
