<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connector_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connector_id')->constrained('connectors')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_owner')->default(false);
            $table->boolean('is_protected')->default(false);
            $table->timestamps();

            $table->unique(['connector_id', 'name']);
            $table->index(['connector_id', 'is_owner']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connector_roles');
    }
};
