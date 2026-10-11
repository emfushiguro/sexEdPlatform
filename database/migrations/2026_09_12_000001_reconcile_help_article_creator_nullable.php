<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE help_articles DROP FOREIGN KEY help_articles_created_by_foreign');
            DB::statement('ALTER TABLE help_articles MODIFY created_by BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE help_articles ADD CONSTRAINT help_articles_created_by_foreign FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL');

            return;
        }

        Schema::table('help_articles', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->unsignedBigInteger('created_by')->nullable()->change();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE help_articles DROP FOREIGN KEY help_articles_created_by_foreign');
            DB::statement('ALTER TABLE help_articles MODIFY created_by BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE help_articles ADD CONSTRAINT help_articles_created_by_foreign FOREIGN KEY (created_by) REFERENCES users (id)');

            return;
        }

        Schema::table('help_articles', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }
};
