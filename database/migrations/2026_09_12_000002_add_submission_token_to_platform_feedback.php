<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_feedback') || Schema::hasColumn('platform_feedback', 'submission_token')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE platform_feedback ADD submission_token CHAR(36) NULL');

            DB::table('platform_feedback')
                ->whereNull('submission_token')
                ->orderBy('id')
                ->get(['id'])
                ->each(fn ($feedback): int => DB::table('platform_feedback')->where('id', $feedback->id)->update([
                    'submission_token' => (string) Str::uuid(),
                ]));

            DB::statement('ALTER TABLE platform_feedback MODIFY submission_token CHAR(36) NOT NULL');
            DB::statement('ALTER TABLE platform_feedback ADD UNIQUE KEY platform_feedback_submission_token_unique (submission_token)');

            return;
        }

        Schema::table('platform_feedback', function (Blueprint $table): void {
            $table->uuid('submission_token')->nullable();
        });

        DB::table('platform_feedback')
            ->whereNull('submission_token')
            ->orderBy('id')
            ->get(['id'])
            ->each(fn ($feedback): int => DB::table('platform_feedback')->where('id', $feedback->id)->update([
                'submission_token' => (string) Str::uuid(),
            ]));

        Schema::table('platform_feedback', function (Blueprint $table): void {
            $table->unique('submission_token', 'platform_feedback_submission_token_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_feedback') || ! Schema::hasColumn('platform_feedback', 'submission_token')) {
            return;
        }

        Schema::table('platform_feedback', function (Blueprint $table): void {
            $table->dropUnique('platform_feedback_submission_token_unique');
            $table->dropColumn('submission_token');
        });
    }
};
