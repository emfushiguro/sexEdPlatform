<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connector_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connector_id')->constrained('connectors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('connector_role_id')->constrained('connector_roles')->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->unique(['connector_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['connector_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connector_memberships');
    }
};
