<?php

namespace Tests\Feature\DependentSupport;

use App\Models\DependentSupportInformationAudit;
use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DependentSupportSchemaTest extends TestCase
{
    public function test_schema_and_relationship_permission_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('dependent_support_profiles', [
            'dependent_user_id',
            'relevant_health_considerations',
            'accessibility_support_needs',
            'additional_relevant_information',
            'privacy_notice_version',
            'purpose_acknowledged_at',
            'purpose_acknowledged_by_user_id',
            'created_by_user_id',
            'updated_by_user_id',
        ]));
        $this->assertTrue(Schema::hasColumns('dependent_support_information_audits', [
            'dependent_user_id', 'actor_user_id', 'parent_child_account_id',
            'action', 'changed_fields', 'occurred_at',
        ]));
        $this->assertTrue(Schema::hasColumn('parent_child_accounts', 'can_manage_support_information'));
    }

    public function test_support_content_is_encrypted_and_one_record_per_dependent(): void
    {
        $dependent = User::factory()->create();
        $actor = User::factory()->create();

        $profile = DependentSupportProfile::query()->create([
            'dependent_user_id' => $dependent->id,
            'relevant_health_considerations' => 'Synthetic participation consideration',
            'accessibility_support_needs' => 'Synthetic reading support',
            'additional_relevant_information' => 'Synthetic additional support context',
            'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
            'purpose_acknowledged_at' => now(),
            'purpose_acknowledged_by_user_id' => $actor->id,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);

        $raw = DB::table('dependent_support_profiles')->where('id', $profile->id)->first();

        $this->assertNotSame('Synthetic participation consideration', $raw->relevant_health_considerations);
        $this->assertNotSame('Synthetic reading support', $raw->accessibility_support_needs);
        $this->assertNotSame('Synthetic additional support context', $raw->additional_relevant_information);
        $this->assertSame('Synthetic participation consideration', $profile->fresh()->relevant_health_considerations);
        $this->assertSame('Synthetic additional support context', $profile->fresh()->additional_relevant_information);
        $this->assertSame($profile->id, $dependent->dependentSupportProfile()->firstOrFail()->id);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DependentSupportProfile::query()->create([
            'dependent_user_id' => $dependent->id,
            'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
            'purpose_acknowledged_at' => now(),
            'purpose_acknowledged_by_user_id' => $actor->id,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);
    }

    public function test_relationship_permission_defaults_false_and_is_queryable(): void
    {
        $guardian = User::factory()->create();
        $dependent = User::factory()->create();
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
        ]);

        $this->assertFalse($relationship->fresh()->can_manage_support_information);
        $this->assertFalse(ParentChildAccount::query()
            ->withPermission('can_manage_support_information')
            ->whereKey($relationship->id)
            ->exists());
    }

    public function test_audit_casts_changed_field_names_without_content(): void
    {
        $dependent = User::factory()->create();
        $audit = DependentSupportInformationAudit::query()->create([
            'dependent_user_id' => $dependent->id,
            'action' => DependentSupportInformationAudit::ACTION_UPDATED,
            'changed_fields' => ['accessibility_support_needs'],
            'occurred_at' => now(),
        ]);

        $this->assertSame(['accessibility_support_needs'], $audit->fresh()->changed_fields);
    }
}
