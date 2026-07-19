<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connector_membership_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('connector_id')->constrained('connectors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['connector_id', 'status']);
            $table->unique(['connector_id', 'user_id', 'status'], 'conn_member_request_user_status_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connector_membership_requests');
    }
};
