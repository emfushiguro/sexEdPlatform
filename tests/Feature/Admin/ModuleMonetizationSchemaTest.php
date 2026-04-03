<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleMonetizationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_monetization_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('commission_policies'));
        $this->assertTrue(Schema::hasTable('module_sale_ledgers'));
        $this->assertTrue(Schema::hasTable('instructor_earnings_visibility'));
        $this->assertTrue(Schema::hasTable('commission_policy_audits'));
    }

    public function test_module_sale_ledgers_has_required_columns_and_constraints(): void
    {
        $this->assertTrue(Schema::hasColumns('module_sale_ledgers', [
            'payment_id',
            'module_purchase_id',
            'module_id',
            'instructor_id',
            'learner_id',
            'currency',
            'gross_amount',
            'basis_amount',
            'commission_percent_snapshot',
            'commission_amount',
            'instructor_earnings_amount',
            'tax_basis_snapshot',
            'refund_policy_snapshot',
            'sale_status',
            'payout_status',
            'occurred_at',
        ]));

        $indexes = Schema::getIndexes('module_sale_ledgers');
        $hasUniquePaymentIdIndex = collect($indexes)->contains(function ($index): bool {
            $columns = $index['columns'] ?? [];
            $isUnique = (bool) ($index['unique'] ?? false);

            return $isUnique && $columns === ['payment_id'];
        });

        $this->assertTrue($hasUniquePaymentIdIndex);
    }

    public function test_commission_policy_and_visibility_tables_have_core_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('commission_policies', [
            'scope_type',
            'scope_id',
            'commission_percent',
            'tax_basis',
            'refund_policy',
            'is_active',
            'effective_from',
            'effective_to',
            'updated_by',
        ]));

        $this->assertTrue(Schema::hasColumns('instructor_earnings_visibility', [
            'module_sale_ledger_id',
            'instructor_id',
            'deleted_at',
            'deleted_by',
            'delete_reason',
        ]));

        $this->assertTrue(Schema::hasColumns('commission_policy_audits', [
            'actor_admin_id',
            'action_type',
            'before_payload',
            'after_payload',
            'request_meta',
            'occurred_at',
        ]));
    }
}
