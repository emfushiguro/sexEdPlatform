<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCreatorProfileSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creator_profiles_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('admin_creator_profiles'));

        $this->assertTrue(Schema::hasColumn('admin_creator_profiles', 'user_id'));
        $this->assertTrue(Schema::hasColumn('admin_creator_profiles', 'public_display_name'));
        $this->assertTrue(Schema::hasColumn('admin_creator_profiles', 'bio'));
        $this->assertTrue(Schema::hasColumn('admin_creator_profiles', 'affiliation'));
        $this->assertTrue(Schema::hasColumn('admin_creator_profiles', 'avatar_path'));
        $this->assertTrue(Schema::hasColumn('admin_creator_profiles', 'show_individual_attribution'));
    }
}
