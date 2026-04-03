<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_policy_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 64);
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->json('request_meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['actor_admin_id', 'occurred_at'], 'idx_commission_policy_audits_actor_occurred');
            $table->index(['action_type', 'occurred_at'], 'idx_commission_policy_audits_action_occurred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_policy_audits');
    }
};
