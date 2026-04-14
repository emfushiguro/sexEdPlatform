<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPermissionDescriptionDisplayTest extends TestCase
{
    public function test_permissions_table_has_description_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('permissions', 'description'),
            'Expected permissions.description column to exist for UI descriptions.'
        );
    }
}
