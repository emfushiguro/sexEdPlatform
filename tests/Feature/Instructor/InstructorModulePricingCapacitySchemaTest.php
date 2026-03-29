<?php

namespace Tests\Feature\Instructor;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstructorModulePricingCapacitySchemaTest extends TestCase
{
    public function test_modules_table_has_pricing_and_capacity_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('modules', [
            'access_type',
            'price_amount',
            'price_currency',
            'enrollment_limit',
        ]));
    }
}
