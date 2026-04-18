<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamification_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('gamification_policies')->cascadeOnDelete();
            $table->json('policy_payload');
            $table->string('version_label', 120)->nullable();
            $table->text('change_summary')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['policy_id', 'created_at'], 'idx_gamification_policy_versions_policy_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_policy_versions');
    }
};
