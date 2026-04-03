<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Schema;
use Tests\DatabaseTestCase;

class AdminContentGovernanceSchemaTest extends DatabaseTestCase
{
    public function test_content_governance_schema_exists(): void
    {
        $this->assertTrue(Schema::hasTable('module_revisions'));
        $this->assertTrue(Schema::hasTable('module_review_requests'));

        $this->assertTrue(Schema::hasColumns('modules', [
            'content_owner_type',
            'published_revision_id',
            'published_by_admin_id',
        ]));

        $this->assertTrue(Schema::hasColumns('module_revisions', [
            'module_id',
            'revision_number',
            'snapshot_payload',
            'submitted_by',
            'status',
            'submitted_at',
            'reviewed_at',
            'reviewed_by',
            'review_feedback',
        ]));

        $this->assertTrue(Schema::hasColumns('module_review_requests', [
            'module_id',
            'module_revision_id',
            'status',
            'submitted_by',
            'reviewed_by',
            'submitted_at',
            'reviewed_at',
            'feedback',
        ]));
    }
}
