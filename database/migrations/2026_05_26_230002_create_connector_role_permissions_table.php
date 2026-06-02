<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connector_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connector_role_id')->constrained('connector_roles')->cascadeOnDelete();
            $table->string('permission_key');
            $table->timestamps();

            $table->unique(['connector_role_id', 'permission_key'], 'conn_role_perm_unique');
            $table->index('permission_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connector_role_permissions');
    }
};
