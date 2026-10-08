<?php

namespace Tests\Feature\Support;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HelpCenterSchemaMigrationTest extends TestCase
{
    public function test_migrations_repair_a_legacy_non_nullable_creator_column(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The legacy schema reproduction uses MySQL column metadata.');
        }

        DB::table('migrations')
            ->where('migration', '2026_09_12_000001_reconcile_help_article_creator_nullable')
            ->delete();

        DB::statement('ALTER TABLE help_articles DROP FOREIGN KEY help_articles_created_by_foreign');
        DB::statement('ALTER TABLE help_articles MODIFY created_by BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE help_articles ADD CONSTRAINT help_articles_created_by_foreign FOREIGN KEY (created_by) REFERENCES users (id)');

        $this->artisan('migrate')->assertExitCode(0);

        $column = DB::selectOne(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'help_articles' AND COLUMN_NAME = 'created_by'"
        );

        $this->assertSame('YES', $column->IS_NULLABLE);
    }
}
