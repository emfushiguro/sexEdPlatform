<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->string('organization_email')->nullable()->unique();
            $table->string('contact_number', 30);
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->text('verification_notes')->nullable();
            $table->string('city_code')->index();
            $table->string('barangay_code')->index();
            $table->string('address_line', 500);
            $table->string('status')->default('pending')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('primary_representative_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index(['created_by', 'status']);
            $table->index(['primary_representative_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connectors');
    }
};
