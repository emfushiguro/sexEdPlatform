<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_identity_verifications', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'pathway', 'superseded_at'],
                'learner_idv_user_pathway_history_idx',
            );
        });

        Schema::table('learner_identity_verifications', function (Blueprint $table): void {
            $table->dropUnique('learner_idv_user_pathway_uq');
        });
    }

    public function down(): void
    {
        // Forward-only history preservation migration.
    }
};
