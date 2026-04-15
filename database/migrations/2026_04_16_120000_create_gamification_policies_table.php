<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamification_policies', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false)->index();
            $table->json('policy_payload');
            $table->string('version_label', 120)->nullable();
            $table->text('change_summary')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'updated_at'], 'idx_gamification_policies_active_updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_policies');
    }
};
