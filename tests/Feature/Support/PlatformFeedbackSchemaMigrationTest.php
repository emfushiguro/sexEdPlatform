<?php

namespace Tests\Feature\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformFeedbackSchemaMigrationTest extends TestCase
{
    public function test_reconciliation_migration_restores_submission_token_on_a_legacy_table(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The legacy schema reproduction uses MySQL index metadata.');
        }

        $hadColumn = Schema::hasColumn('platform_feedback', 'submission_token');

        if ($hadColumn) {
            DB::statement('ALTER TABLE platform_feedback DROP INDEX platform_feedback_submission_token_unique');
            DB::statement('ALTER TABLE platform_feedback DROP COLUMN submission_token');
        }

        try {
            DB::table('migrations')
                ->where('migration', '2026_09_12_000002_add_submission_token_to_platform_feedback')
                ->delete();

            $this->artisan('migrate')->assertExitCode(0);

            $this->assertTrue(Schema::hasColumn('platform_feedback', 'submission_token'));
            $this->assertNotEmpty(DB::select(
                "SHOW INDEX FROM platform_feedback WHERE Key_name = 'platform_feedback_submission_token_unique'"
            ));
        } finally {
            if (! Schema::hasColumn('platform_feedback', 'submission_token')) {
                Schema::table('platform_feedback', function ($table): void {
                    $table->uuid('submission_token')->unique();
                });
            }
        }
    }
}
